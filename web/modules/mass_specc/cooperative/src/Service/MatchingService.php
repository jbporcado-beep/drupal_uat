<?php

namespace Drupal\cooperative\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\cooperative\Utility\DomainLists;
/**
 * Provides reusable entity matching logic across modules.
 */
class MatchingService
{

    protected $entityTypeManager;

    public function __construct(EntityTypeManagerInterface $entityTypeManager)
    {
        $this->entityTypeManager = $entityTypeManager;
    }

    /**
     * Match an Individual node by basic info.
     */
    public function matchIndividual(array $data): array
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('type', 'individual');

        if (!empty($data['first_name'])) {
            $query->condition('field_first_name', mb_strtolower(trim($data['first_name'])), '=');
        }

        if (!empty($data['last_name'])) {
            $query->condition('field_last_name', mb_strtolower(trim($data['last_name'])), '=');
        }

        if (!empty($data['dob'])) {
            $query->condition('field_date_of_birth', trim($data['dob']), '=');
        }

        if (!empty($data['gender'])) {
            $gender = strtoupper(trim($data['gender']));
            $query->condition('field_gender', $gender, '=');
        }

        return array_values($query->execute());
    }

    /**
     * Match a Member node by personal info or linked individual.
     */
    public function matchMember(array $data): array
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('type', 'member');

        if (!empty($data['first_name'])) {
            $query->condition('field_first_name', mb_strtolower(trim($data['first_name'])), '=');
        }
        if (!empty($data['last_name'])) {
            $query->condition('field_last_name', mb_strtolower(trim($data['last_name'])), '=');
        }
        if (!empty($data['dob'])) {
            $query->condition('field_date_of_birth', trim($data['dob']), '=');
        }
        if (!empty($data['gender'])) {
            $query->condition('field_gender', strtoupper(trim($data['gender'])), '=');
        }

        if (!empty($data['individual_id'])) {
            $query->condition('field_individual', $data['individual_id']);
        }
        return array_values($query->execute());
    }

    /**
     * Fuzzy match Address nodes by fulladdress fields.
     */
    public function matchAddress(string $input): array
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $address_input = mb_strtolower(trim($input));

        if (mb_strlen($address_input) < 6) {
            return [];
        }

        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('type', 'address');

        $group = $query->orConditionGroup()
            ->condition('field_address1_fulladdress', $address_input, 'CONTAINS')
            ->condition('field_address2_fulladdress', $address_input, 'CONTAINS');

        $query->condition($group);
        $address_ids = $query->execute();

        if (empty($address_ids)) {
            $fuzzy_matches = [];
            $all_addresses = $storage->loadMultiple(
                $storage->getQuery()
                    ->accessCheck(FALSE)
                    ->condition('type', 'address')
                    ->execute()
            );

            foreach ($all_addresses as $address_node) {
                $a1 = mb_strtolower($address_node->get('field_address1_fulladdress')->value ?? '');
                $a2 = mb_strtolower($address_node->get('field_address2_fulladdress')->value ?? '');

                if ($a1 === '' && $a2 === '') {
                    continue;
                }

                similar_text($address_input, $a1, $p1);
                similar_text($address_input, $a2, $p2);

                if ($p1 >= 70 || $p2 >= 70) {
                    $fuzzy_matches[] = $address_node->id();
                }
            }

            return $fuzzy_matches;
        }

        return array_keys($address_ids);
    }


    public function matchIdentification(array $ids): array
    {
        $matched_identification_ids = [];

        if (empty($ids)) {
            return $matched_identification_ids;
        }

        $storage = $this->entityTypeManager->getStorage('node');
        $query_ids = [];

        $identification_type_keys = array_map('strval', array_keys(DomainLists::IDENTIFICATION_TYPE_DOMAIN));
        $id_type_keys = array_map('strval', array_keys(DomainLists::ID_TYPE_DOMAIN));

        foreach ($ids as $id_item) {
            $group = strtolower(trim((string) ($id_item['group'] ?? '')));
            $id_type = trim((string) ($id_item['id_type'] ?? ''));
            $id_value = trim((string) ($id_item['id_number'] ?? ''));

            if ($id_type === '' || $id_value === '') {
                continue;
            }

            $query = $storage->getQuery()
                ->accessCheck(FALSE)
                ->condition('type', 'identification')
                ->condition('status', 1);

            $use_identification_branch = null;
            if ($group === 'identification') {
                $use_identification_branch = true;
            } elseif ($group === 'id' || $group === 'other_id') {
                $use_identification_branch = false;
            } else {
                if (in_array($id_type, $identification_type_keys, true) && !in_array($id_type, $id_type_keys, true)) {
                    $use_identification_branch = true;
                } elseif (in_array($id_type, $id_type_keys, true) && !in_array($id_type, $identification_type_keys, true)) {
                    $use_identification_branch = false;
                } else {
                    $use_identification_branch = false;
                }
            }

            if ($use_identification_branch) {
                $and1 = $query->andConditionGroup()
                    ->condition('field_identification1_type', $id_type)
                    ->condition('field_identification1_number', $id_value);
                $and2 = $query->andConditionGroup()
                    ->condition('field_identification2_type', $id_type)
                    ->condition('field_identification2_number', $id_value);

                $or = $query->orConditionGroup()->condition($and1)->condition($and2);
                $query->condition($or);
            } else {
                // use id fields
                $and1 = $query->andConditionGroup()
                    ->condition('field_id1_type', $id_type)
                    ->condition('field_id1_number', $id_value);
                $and2 = $query->andConditionGroup()
                    ->condition('field_id2_type', $id_type)
                    ->condition('field_id2_number', $id_value);

                $or = $query->orConditionGroup()->condition($and1)->condition($and2);
                $query->condition($or);
            }

            $result = $query->execute();

            if (!empty($result)) {
                $query_ids = array_merge($query_ids, array_keys($result));
            }
        }

        return array_unique($query_ids);
    }


    public function matchContact(array $contact_data): array
    {
        if (empty($contact_data['contact_type']) || empty($contact_data['contact_value'])) {
            return [];
        }

        $type = trim((string) $contact_data['contact_type']);
        $value = trim((string) $contact_data['contact_value']);

        $normalized_value = strtolower(preg_replace('/[\s\-]+/', '', $value));

        $storage = \Drupal::entityTypeManager()->getStorage('node');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('type', 'contact');

        $and1 = $query->andConditionGroup()
            ->condition('field_contact1_type', $type)
            ->condition('field_contact1_value', '%' . $normalized_value . '%', 'LIKE');

        $and2 = $query->andConditionGroup()
            ->condition('field_contact2_type', $type)
            ->condition('field_contact2_value', '%' . $normalized_value . '%', 'LIKE');

        $or = $query->orConditionGroup()
            ->condition($and1)
            ->condition($and2);

        $query->condition($or);

        $contact_ids = $query->execute();

        return array_values($contact_ids);
    }
}
