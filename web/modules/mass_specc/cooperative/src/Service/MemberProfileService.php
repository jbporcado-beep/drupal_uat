<?php

namespace Drupal\cooperative\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\node\Entity\Node;
use Drupal\node\NodeInterface;

class MemberProfileService
{
    protected $entityTypeManager;
    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }
    public function findOrCreateMemberProfile(array $individual_data): ?NodeInterface
    {
        $first = mb_strtolower(trim($individual_data['first_name'] ?? ''));
        $last = mb_strtolower(trim($individual_data['last_name'] ?? ''));
        $dob = $individual_data['dob'] ?? '';
        $gender = strtolower($individual_data['gender'] ?? '');

        if (empty($first) || empty($last) || empty($dob) || empty($gender)) {
            return NULL;
        }

        $expected_coop = $this->normalize($individual_data['provider_code'] ?? '');
        $expected_branch = $this->normalize($individual_data['branch_code'] ?? '');
        $expected_subject = $this->normalize($individual_data['provider_subj_no'] ?? '');

        $incomingKeys = $this->collectIdentificationKeys($individual_data['identification'] ?? null);
        $incomingMap = [];
        foreach ($incomingKeys as $k) {
            $parts = explode('|', $k, 2);
            if (count($parts) === 2) {
                $incomingMap[$this->normalize($parts[0])] = $this->normalize($parts[1]);
            }
        }

        $query = $this->entityTypeManager->getStorage('node')->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', 'member')
            ->condition('status', 1)
            ->condition('field_first_name', $first)
            ->condition('field_last_name', $last)
            ->condition('field_date_of_birth', $dob)
            ->condition('field_gender', $gender);

        $results = $query->execute();

        $individualCache = [];
        $identificationCache = [];

        if (!empty($results)) {
            foreach ($results as $member_nid) {
                $memberProfile = Node::load($member_nid);
                if (!$memberProfile) {
                    continue;
                }

                if (!$memberProfile->hasField('field_individual_profiles')) {
                    continue;
                }

                $refs = $memberProfile->get('field_individual_profiles')->getValue();
                if (empty($refs)) {
                    continue;
                }

                $isExactMatch = FALSE;
                $shouldSkipMember = FALSE;

                foreach ($refs as $ref) {
                    $target_id = $ref['target_id'] ?? NULL;
                    if (empty($target_id)) {
                        continue;
                    }

                    if (!isset($individualCache[$target_id])) {
                        $individualCache[$target_id] = Node::load($target_id);
                    }
                    $indiv = $individualCache[$target_id];
                    if (!$indiv) {
                        continue;
                    }

                    $coop_val = $indiv->hasField('field_provider_code') ? $this->normalize($indiv->get('field_provider_code')->value ?? '') : '';
                    $subject_val = $indiv->hasField('field_provider_subject_no') ? $this->normalize($indiv->get('field_provider_subject_no')->value ?? '') : '';

                    if ($subject_val !== '' && $subject_val === $expected_subject) {
                        if ($coop_val !== '' && $coop_val === $expected_coop) {
                            $isExactMatch = TRUE;
                            break;
                        }
                        continue;
                    }

                    if ($coop_val !== '' && $coop_val === $expected_coop) {
                        if ($subject_val !== '') {
                            $shouldSkipMember = TRUE;
                            break;
                        }
                    }
                }

                if ($isExactMatch) {
                    return $memberProfile;
                }

                if ($shouldSkipMember) {
                    continue;
                }

                if (!empty($incomingMap)) {
                    $memberMatchedByIdentification = FALSE;
                    $memberHasConflictingIdentification = FALSE;

                    foreach ($refs as $ref) {
                        $target_id = $ref['target_id'] ?? NULL;
                        if (empty($target_id)) {
                            continue;
                        }

                        if (!isset($individualCache[$target_id])) {
                            $individualCache[$target_id] = Node::load($target_id);
                        }
                        $indivNode = $individualCache[$target_id];
                        if (!$indivNode) {
                            continue;
                        }

                        if (!$indivNode->hasField('field_identification')) {
                            continue;
                        }

                        $idRefs = $indivNode->get('field_identification')->getValue();
                        if (empty($idRefs)) {
                            continue;
                        }

                        $refMap = [];
                        foreach ($idRefs as $idRef) {
                            $idNid = $idRef['target_id'] ?? null;
                            if (empty($idNid)) {
                                continue;
                            }
                            if (!isset($identificationCache[$idNid])) {
                                $idNode = Node::load($idNid);
                                if (!$idNode) {
                                    $identificationCache[$idNid] = [];
                                } else {
                                    $identificationCache[$idNid] = $this->collectIdentificationKeysFromIdentificationNode($idNode);
                                }
                            }
                            if (!empty($identificationCache[$idNid])) {
                                foreach ($identificationCache[$idNid] as $kk) {
                                    $parts = explode('|', $kk, 2);
                                    if (count($parts) === 2) {
                                        $t = $this->normalize($parts[0]);
                                        $n = $this->normalize($parts[1]);
                                        $refMap[$t] = $n;
                                    }
                                }
                            }
                        }

                        if (!empty($refMap)) {
                            foreach ($incomingMap as $inType => $inNumber) {
                                if (isset($refMap[$inType])) {
                                    if ($refMap[$inType] === $inNumber) {
                                        $memberMatchedByIdentification = TRUE;
                                        break 2;
                                    } else {
                                        $memberHasConflictingIdentification = TRUE;
                                        break 2;
                                    }
                                }
                            }
                        }
                    }

                    if ($memberMatchedByIdentification) {
                        return $memberProfile;
                    }

                    if ($memberHasConflictingIdentification) {
                        continue;
                    }
                }
            }
        }

        $profile = Node::create([
            'type' => 'member',
            'title' => "{$first} {$last}",
            'field_first_name' => $first,
            'field_last_name' => $last,
            'field_date_of_birth' => $dob,
            'field_gender' => $gender,
        ]);

        $profile->save();

        return $profile;
    }

    protected function collectIdentificationKeys($identificationDto): array
    {
        $keys = [];
        if (empty($identificationDto)) {
            return [];
        }

        $pairs = [
            ['identification1Type', 'identification1Number'],
            ['identification2Type', 'identification2Number'],
            ['id1Type', 'id1Number'],
            ['id2Type', 'id2Number'],
        ];

        foreach ($pairs as [$typeField, $numField]) {
            $type = '';
            $num = '';

            if (is_object($identificationDto)) {
                $type = (string) ($identificationDto->{$typeField} ?? '');
                $num = (string) ($identificationDto->{$numField} ?? '');
            } elseif (is_array($identificationDto)) {
                $type = (string) ($identificationDto[$typeField] ?? '');
                $num = (string) ($identificationDto[$numField] ?? '');
            }

            $type = trim($type);
            $num = preg_replace('/\s+/', '', trim($num));
            $num = preg_replace('/[^\PC\s]/u', '', $num);

            if ($type !== '' && $num !== '') {
                $keys[] = "{$type}|{$num}";
            }
        }

        return array_values(array_unique($keys));
    }


    protected function collectIdentificationKeysFromIdentificationNode(NodeInterface $idNode): array
    {
        $keys = [];

        $fields = [
            ['field_identification1_type', 'field_identification1_number'],
            ['field_identification2_type', 'field_identification2_number'],
            ['field_id1_type', 'field_id1_number'],
            ['field_id2_type', 'field_id2_number'],
        ];

        foreach ($fields as [$typeField, $numField]) {
            $type = $idNode->hasField($typeField) ? (string) ($idNode->get($typeField)->value ?? '') : '';
            $num = $idNode->hasField($numField) ? (string) ($idNode->get($numField)->value ?? '') : '';

            $type = trim($type);
            $num = preg_replace('/\s+/', '', trim($num));
            $num = preg_replace('/[^\PC\s]/u', '', $num);

            if ($type !== '' && $num !== '') {
                $keys[] = "{$type}|{$num}";
            }
        }

        return array_values(array_unique($keys));
    }


    protected function normalize(string $s): string
    {
        return mb_strtolower(trim((string) $s));
    }

}
