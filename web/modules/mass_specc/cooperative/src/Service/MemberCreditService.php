<?php

namespace Drupal\cooperative\Service;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DateStringFormatter;
use Drupal\cooperative\Service\MatchingService;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\Core\File\FileSystemInterface;
use Mpdf\Mpdf;
use Mpdf\HTMLParserMode;

use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
/**
 * Service for finding Individuals based on form input.
 */
class MemberCreditService
{
    protected $entityTypeManager;
    protected $database;
    protected $matchingService;

    protected $currentUser;
    protected $activityLogger;

    public function __construct(
        EntityTypeManagerInterface $entityTypeManager,
        Connection $database,
        MatchingService $matchingService,
        UserActivityLogger $activityLogger,
        AccountProxyInterface $currentUser
    ) {
        $this->entityTypeManager = $entityTypeManager;
        $this->database = $database;
        $this->matchingService = $matchingService;
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }

    public function getMatchingIndividual(array $data): array
    {
        $individual_ids = [];

        $base_ids = $this->matchingService->matchIndividual($data);
        if (!empty($base_ids)) {
            $individual_ids = $base_ids;
        }

        if (!empty($data['address'])) {
            $address_ids = $this->matchingService->matchAddress($data['address']);
            if (empty($address_ids)) {
                return [];
            }
            $individual_ids = $this->filterIndividualsByField($individual_ids, 'field_address', $address_ids);
        }

        if (!empty($data['contact_type']) && !empty($data['contact_value'])) {
            $contact_ids = $this->matchingService->matchContact($data);
            if (empty($contact_ids)) {
                return [];
            }
            $individual_ids = $this->filterIndividualsByField($individual_ids, 'field_contact', $contact_ids);
        }

        if (!empty($data['ids'])) {
            $matched_identifications = $this->matchingService->matchIdentification($data['ids']);
            if (empty($matched_identifications)) {
                return [];
            }
            $individual_ids = $this->filterIndividualsByField($individual_ids, 'field_identification', $matched_identifications);
        }

        if (count($individual_ids) === 1) {
            $individual = Node::load(reset($individual_ids));
            if ($individual && $individual->hasField('field_member_profile') && !$individual->get('field_member_profile')->isEmpty()) {
                $member_profile_id = $individual->get('field_member_profile')->target_id;
                return [$member_profile_id];
            }
            return $individual_ids;
        }

        $nodes = Node::loadMultiple($individual_ids);
        $member_profile_ids = [];

        foreach ($nodes as $nid => $node) {
            if ($node && $node->hasField('field_member_profile') && !$node->get('field_member_profile')->isEmpty()) {
                $mpid = $node->get('field_member_profile')->target_id;
                $member_profile_ids[] = (string) $mpid;
            } else {
                $member_profile_ids[] = null;
            }
        }

        $non_empty_count = count(array_filter($member_profile_ids, function ($v) {
            return $v !== null && $v !== '';
        }));

        if ($non_empty_count === count($member_profile_ids)) {
            $unique = array_values(array_unique($member_profile_ids));
            if (count($unique) === 1) {
                return [(int) $unique[0]];
            }
        }

        return array_values($individual_ids);
    }

    /**
     * Filters a list of individual node IDs by a field referencing specific entities.
     */
    protected function filterIndividualsByField(array $individual_ids, string $field_name, array $match_ids): array
    {
        $storage = $this->entityTypeManager->getStorage('node');
        $query = $storage->getQuery()
            ->accessCheck(FALSE)
            ->condition('status', 1)
            ->condition('type', 'individual');

        if (!empty($individual_ids)) {
            $query->condition('nid', $individual_ids, 'IN');
        }

        if (!empty($match_ids)) {
            $query->condition($field_name, $match_ids, 'IN');
        }

        return array_values($query->execute());
    }

    public function buildReportData(int $member_nid): array
    {
        $member = Node::load($member_nid);
        if (!$member || $member->bundle() !== 'member') {
            return [];
        }

        $nodeFieldValue = fn(Node $n, string $field) => ($n->hasField($field) && !$n->get($field)->isEmpty()) ? ($n->get($field)->value ?? '') : '';
        $loadTargetNode = function ($target_id) {
            return $target_id ? Node::load($target_id) : NULL;
        };

        $individuals = [];
        if ($member->hasField('field_individual_profiles') && !$member->get('field_individual_profiles')->isEmpty()) {
            $refs = $member->get('field_individual_profiles')->getValue();
            foreach ($refs as $ref) {
                $tid = $ref['target_id'] ?? NULL;
                if ($tid) {
                    $ind = Node::load($tid);
                    if ($ind instanceof Node && $ind->bundle() === 'individual') {
                        $individuals[] = $ind;
                    }
                }
            }
        }

        $primary_individual = NULL;
        if (!empty($individuals) && $individuals[0] instanceof Node && $individuals[0]->bundle() === 'individual') {
            $primary_individual = $individuals[0];
        }

        $subject = [
            'msp_member_code' => $nodeFieldValue($member, 'field_msp_subject_code'),
            'last_name' => $nodeFieldValue($member, 'field_last_name'),
            'first_name' => $nodeFieldValue($member, 'field_first_name'),
            'middle_name' => $nodeFieldValue($member, 'field_middle_name'),
            'dob' => DateStringFormatter::formatDateString($nodeFieldValue($member, 'field_date_of_birth')),
            'gender' => match (strtolower($nodeFieldValue($member, 'field_gender'))) {
                'm' => 'Male',
                'f' => 'Female',
                default => '',
            },
            'code' => $nodeFieldValue($member, 'field_subject_code'),
            'last_update' => $member->getChangedTime() ? date('d/m/Y', $member->getChangedTime()) : '',
            'title' => DomainLists::TITLE_DOMAIN[$primary_individual ? $nodeFieldValue($primary_individual, 'field_title') : $nodeFieldValue($member, 'field_title')] ?? '',
            'prev_last_name' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_previous_last_name') : $nodeFieldValue($member, 'field_previous_last_name'),
            'suffix' => $nodeFieldValue($member, 'field_suffix'),
            'alias' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_alias') : $nodeFieldValue($member, 'field_alias'),
            'nationality' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_nationality') : $nodeFieldValue($member, 'field_nationality'),
            'dob_raw' => $nodeFieldValue($member, 'field_date_of_birth'),
            'pob' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_place_of_birth') : $nodeFieldValue($member, 'field_place_of_birth'),
            'cob' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_country_of_birth_code') : $nodeFieldValue($member, 'field_country_of_birth_code'),
            'resident' => (function () use ($primary_individual, $member, $nodeFieldValue) {
                $raw = $primary_individual
                    ? $nodeFieldValue($primary_individual, 'field_resident')
                    : $nodeFieldValue($member, 'field_resident');
                $value = strtolower(trim((string) $raw));
                return in_array($value, ['1', 'yes', 'true'], true) ? 'Resident' : 'Non Resident';
            })(),
            'civ_status' => DomainLists::CIVIL_STATUS_DOMAIN[$primary_individual ? $nodeFieldValue($primary_individual, 'field_civil_status') : $nodeFieldValue($member, 'field_civil_status')] ?? '',
            'dependents' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_number_of_dependents') : $nodeFieldValue($member, 'field_number_of_dependents'),
            'cars' => $primary_individual ? $nodeFieldValue($primary_individual, 'field_cars_owned') : $nodeFieldValue($member, 'field_cars_owned'),
        ];

        $addresses = [];
        $contacts = [];
        $identifications = [];
        $ids = [];

        $employment = [];
        $spouse = [];
        $mother = [];
        $father = [];

        foreach ($individuals as $ind) {
            if (!($ind instanceof Node) || $ind->bundle() !== 'individual') {
                continue;
            }

            if ($ind->hasField('field_address') && !$ind->get('field_address')->isEmpty()) {
                $addr_tid = $ind->get('field_address')->target_id;
                $addr_node = $loadTargetNode($addr_tid);
                if ($addr_node) {
                    $addrGet = fn($f) => $addr_node->hasField($f) && !$addr_node->get($f)->isEmpty() ? ($addr_node->get($f)->value ?? '') : '';
                    $last_update = $addr_node->getChangedTime() ? date('d/m/Y', $addr_node->getChangedTime()) : '';
                    // Address 1
                    if ($addrGet('field_address1_fulladdress')) {
                        $addresses[] = [
                            'type' => "MI",
                            'full_address' => $addrGet('field_address1_fulladdress'),
                            'owner_lessee' => '',
                            'occupied_since' => '',
                            'last_update' => $last_update,
                        ];
                    }

                    // Address 2
                    if ($addrGet('field_address2_fulladdress')) {
                        $addresses[] = [
                            'type' => "AI",
                            'full_address' => $addrGet('field_address2_fulladdress'),
                            'owner_lessee' => '',
                            'occupied_since' => '',
                            'last_update' => $last_update,
                        ];
                    }
                }
            }

            // Contact
            if ($ind->hasField('field_contact') && !$ind->get('field_contact')->isEmpty()) {
                $c_tid = $ind->get('field_contact')->target_id;
                $contact_node = $loadTargetNode($c_tid);
                $last_update = $contact_node->getChangedTime() ? date('d/m/Y', $addr_node->getChangedTime()) : '';

                if ($contact_node) {
                    $cGet = fn($f) => $contact_node->hasField($f) && !$contact_node->get($f)->isEmpty() ? ($contact_node->get($f)->value ?? '') : '';
                    $contacts[] = [
                        'type' => DomainLists::CONTACT_TYPE_DOMAIN[$cGet('field_contact1_type')] ?? '',
                        'contact' => $cGet('field_contact1_value'),
                        'last_update' => $last_update,
                    ];
                }
            }

            // Identification + IDs
            if ($ind->hasField('field_identification') && !$ind->get('field_identification')->isEmpty()) {
                $i_tid = $ind->get('field_identification')->target_id;
                $identification_node = $loadTargetNode($i_tid);
                $last_update = $identification_node->getChangedTime() ? date('d/m/Y', $addr_node->getChangedTime()) : '';

                if ($identification_node) {
                    $iGet = fn($f) => $identification_node->hasField($f) && !$identification_node->get($f)->isEmpty() ? ($identification_node->get($f)->value ?? '') : '';

                    if (!empty($iGet('field_identification1_type')) || !empty($iGet('field_identification1_number'))) {
                        $identifications[] = [
                            'type' => DomainLists::IDENTIFICATION_TYPE_DOMAIN[$iGet('field_identification1_type')] ?? $iGet('field_identification1_type'),
                            'code' => $iGet('field_identification1_number'),
                            'last_update' => $last_update,
                        ];
                    }
                    if (!empty($iGet('field_identification2_type')) || !empty($iGet('field_identification2_number'))) {
                        $identifications[] = [
                            'type' => DomainLists::IDENTIFICATION_TYPE_DOMAIN[$iGet('field_identification2_type')] ?? $iGet('field_identification2_type'),
                            'code' => $iGet('field_identification2_number'),
                            'last_update' => $last_update,
                        ];
                    }

                    if (!empty($iGet('field_id1_type'))) {
                        $ids[] = [
                            'type' => DomainLists::ID_TYPE_DOMAIN[$iGet('field_id1_type')] ?? $iGet('field_id1_type'),
                            'number' => $iGet('field_id1_number'),
                            'issue_date' => DateStringFormatter::formatDateString($iGet('field_id1_issuedate')) ?: $iGet('field_id1_issuedate'),
                            'expiry_date' => DateStringFormatter::formatDateString($iGet('field_id1_expirydate')) ?: $iGet('field_id1_expirydate'),
                            'country' => $iGet('field_id1_issuecountry'),
                            'issued_by' => $iGet('field_id1_issuedby'),
                            'last_update' => $last_update,
                        ];
                    }
                    if (!empty($iGet('field_id2_type'))) {
                        $ids[] = [
                            'type' => DomainLists::ID_TYPE_DOMAIN[$iGet('field_id2_type')] ?? $iGet('field_id2_type'),
                            'number' => $iGet('field_id2_number'),
                            'issue_date' => DateStringFormatter::formatDateString($iGet('field_id2_issuedate')) ?: $iGet('field_id2_issuedate'),
                            'expiry_date' => DateStringFormatter::formatDateString($iGet('field_id2_expirydate')) ?: $iGet('field_id2_expirydate'),
                            'country' => $iGet('field_id2_issuecountry'),
                            'issued_by' => $iGet('field_id2_issuedby'),
                            'last_update' => $last_update,
                        ];
                    }
                }
            }
        }

        if ($primary_individual) {
            if ($primary_individual->hasField('field_employment') && !$primary_individual->get('field_employment')->isEmpty()) {
                $e_tid = $primary_individual->get('field_employment')->target_id;
                $employee_node = $loadTargetNode($e_tid);
                if ($employee_node) {
                    $employee_get = fn($f) => $employee_node->hasField($f) && !$employee_node->get($f)->isEmpty() ? ($employee_node->get($f)->value ?? '') : '';
                    $employment = [
                        'trade_name' => $employee_get('field_employ_trade_name'),
                        'occupation' => $employee_get('field_employ_occupation'),
                        'occupation_status' => DomainLists::OCCUPATION_STATUS_DOMAIN[$employee_get('field_employ_occupation_status')] ?? '',
                        'psic' => $employee_get('field_employ_psic'),
                    ];
                }
            }

            if ($primary_individual->hasField('field_family') && !$primary_individual->get('field_family')->isEmpty()) {
                $f_tid = $primary_individual->get('field_family')->target_id;
                $f_node = $loadTargetNode($f_tid);
                if ($f_node) {
                    $fGet = fn($f) => $f_node->hasField($f) && !$f_node->get($f)->isEmpty() ? ($f_node->get($f)->value ?? '') : '';
                    if (!empty($fGet('field_spouse_first_name')) || !empty($fGet('field_spouse_last_name'))) {
                        $spouse = [
                            'first_name' => $fGet('field_spouse_first_name'),
                            'last_name' => $fGet('field_spouse_last_name'),
                            'middle_name' => $fGet('field_spouse_middle_name'),
                        ];
                    }
                    if (!empty($fGet('field_mother_maiden_full_name'))) {
                        $mother = ['full_name' => $fGet('field_mother_maiden_full_name')];
                    }
                    if (!empty($fGet('field_father_first_name')) || !empty($fGet('field_father_last_name'))) {
                        $father = [
                            'first_name' => $fGet('field_father_first_name'),
                            'last_name' => $fGet('field_father_last_name'),
                            'middle_name' => $fGet('field_father_middle_name'),
                            'suffix' => $fGet('field_father_suffix'),
                        ];
                    }
                }
            }
        }


        $normalize_contact_value = function (?string $val): string {
            $val = trim((string) $val);
            if ($val === '') {
                return '';
            }

            if (preg_match('/^[0-9\-\+\s\(\)]+$/', $val)) {
                return preg_replace('/\D+/', '', $val);
            }

            return mb_strtolower(preg_replace('/\s+/', ' ', $val));
        };


        $dedupe_by_key = function (array $items, callable $keyer): array {
            $seen = [];
            $out = [];
            foreach ($items as $item) {
                if (!is_array($item)) {
                    continue;
                }
                $key = (string) $keyer($item);
                if ($key === '') {

                }
                if ($key === '') {

                    if (!isset($seen['__empty__'])) {
                        $seen['__empty__'] = true;
                        $out[] = $item;
                    }
                    continue;
                }
                if (!isset($seen[$key])) {
                    $seen[$key] = true;
                    $out[] = $item;
                }
            }
            return $out;
        };


        $contacts = $dedupe_by_key($contacts, function ($c) use ($normalize_contact_value) {
            $type = isset($c['type']) ? trim((string) $c['type']) : '';
            $val = isset($c['contact']) ? $normalize_contact_value($c['contact']) : '';
            return $type . '|' . $val;
        });


        $identifications = $dedupe_by_key($identifications, function ($i) {
            $type = isset($i['type']) ? trim((string) $i['type']) : '';
            $code = isset($i['code']) ? trim((string) $i['code']) : '';
            return $type . '|' . $code;
        });


        $ids = $dedupe_by_key($ids, function ($id) {
            $type = isset($id['type']) ? trim((string) $id['type']) : '';
            $num = isset($id['number']) ? trim((string) $id['number']) : '';
            return $type . '|' . $num;
        });


        $normalize_addr = function ($addr) {
            $full = isset($addr['full_address']) ? trim((string) $addr['full_address']) : '';
            $type = isset($addr['type']) ? trim((string) $addr['type']) : '';
            if ($full === '') {
                return $type . '|';
            }

            $norm = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $full);
            $norm = mb_strtolower(preg_replace('/\s+/', ' ', $norm));
            return $type . '|' . $norm;
        };

        $addresses = $dedupe_by_key($addresses, function ($a) use ($normalize_addr) {
            return $normalize_addr($a);
        });

        $data = [
            'request_date' => date('d/m/Y'),
            'subject' => $subject,
        ];

        if (!empty($addresses)) {
            $data['addresses'] = $addresses;
        }
        if (!empty($contacts)) {
            $data['contacts'] = $contacts;
        }
        if (!empty($identifications)) {
            $data['identifications'] = $identifications;
        }
        if (!empty($ids)) {
            $data['ids'] = $ids;
        }

        $data['employment'] = $employment ?: [];
        $data['spouse'] = $spouse ?: [];
        $data['mother'] = $mother ?: [];
        $data['father'] = $father ?: [];

        $contracts_payload = $this->getContractsOfMember($member_nid);
        $data['contracts'] = [
            'requested_refused' => $contracts_payload['contracts_requested_refused'],
            'active_closed' => $contracts_payload['contracts_active_closed'],
        ];
        $data['contract_details'] = $contracts_payload['contract_details'];

        $msp_member_code = $subject['msp_member_code'] ?? '';
        $action = "Generated Member Credit report for {$msp_member_code}";

        $this->activityLogger->log(
            $action,
            'node',
            NULL,
            [],
            NULL,
            $this->currentUser
        );

        return $data;
    }

    public function getContractsOfMember(int $member_nid): array
    {
        $member = Node::load($member_nid);
        if (!$member || $member->bundle() !== 'member') {
            return [
                'contracts_requested_refused' => [],
                'contracts_active_closed' => [],
                'contract_details' => [],
            ];
        }

        $getField = fn(Node $n, string $f) => ($n->hasField($f) && !$n->get($f)->isEmpty()) ? ($n->get($f)->value ?? '') : '';

        $individual_ids = [];
        if ($member->hasField('field_individual_profiles') && !$member->get('field_individual_profiles')->isEmpty()) {
            foreach ($member->get('field_individual_profiles')->getValue() as $ref) {
                if (!empty($ref['target_id'])) {
                    $individual_ids[] = (int) $ref['target_id'];
                }
            }
        }

        if (empty($individual_ids)) {
            return [
                'contracts_requested_refused' => [],
                'contracts_active_closed' => [],
                'contract_details' => [],
            ];
        }

        $contract_types = ['installment_contract', 'noninstallment_contract'];

        $query = \Drupal::entityQuery('node')
            ->accessCheck(FALSE)
            ->condition('type', $contract_types, 'IN')
            ->condition('field_subject.target_id', $individual_ids, 'IN')
            ->condition('status', 1);
        $nids = $query->execute();

        if (empty($nids)) {
            return [
                'contracts_requested_refused' => [],
                'contracts_active_closed' => [],
                'contract_details' => [],
            ];
        }

        $contracts = Node::loadMultiple($nids);

        $group1 = ['Requested', 'Refused', 'Renounced'];
        $group2 = ['Active', 'Closed', 'Closed in Advance'];

        $result = [
            'contracts_requested_refused' => [],
            'contracts_active_closed' => [],
            'contract_details' => [],
        ];

        $contract_counter = 0;
        foreach ($contracts as $contract) {
            if (!$contract instanceof Node) {
                continue;
            }
            $contract_counter++;

            $phase = (string) $getField($contract, 'field_contract_phase');
            $phase_normalized = trim(mb_strtolower($phase));

            $summary_item = [
                'no' => $contract_counter,
                'msp_contract_code' => $getField($contract, 'field_msp_contract_code') ?: '—',
                'contract_type' => DomainLists::CI_CONTRACT_TYPE_DOMAIN[$getField($contract, 'field_contract_type')]
                    ?? DomainLists::CN_CONTRACT_TYPE_DOMAIN[$getField($contract, 'field_contract_type')]
                    ?? '—',
                'contract_phase' => DomainLists::CONTRACT_PHASE_DOMAIN[$phase] ?: "-",
                'role' => $getField($contract, 'field_role') ?: '—',
                'financed_amount' => $getField($contract, 'field_financed_amount') ?: '—',
                'installments_number' => $getField($contract, 'field_installments_no') ?: '—',
                'payment' => $getField($contract, 'field_monthly_payment_amount') ?: '—',
                'payment_periodicity' => $getField($contract, 'field_payment_periodicity') ?: '—',
                'last_update' => $contract->getChangedTime() ? date('d/m/Y', $contract->getChangedTime()) : '—',
                'outstanding_balance' => $getField($contract, 'field_outstanding_balance') ?: '—',
                'overdue_amount' => $getField($contract, 'field_overdue_payments_amount') ?: '—',
                'start_date' => DateStringFormatter::formatDateString($getField($contract, 'field_contract_start_date') ?: ''),
                'end_date' => DateStringFormatter::formatDateString($getField($contract, 'field_contract_end_planned_date') ?: ''),
            ];

            $g1_normalized = array_map(fn($v) => mb_strtolower($v), $group1);

            if (in_array($phase_normalized, $g1_normalized, TRUE)) {
                $result['contracts_requested_refused'][] = $summary_item;
            } else {
                $result['contracts_active_closed'][] = $summary_item;
            }

            $detail = [];
            $detail['msp_contract_code'] = $summary_item['msp_contract_code'];
            $detail['provider_contract_no'] = $getField($contract, 'field_provider_contract_no') ?: '—';
            $detail['role'] = $summary_item['role'];

            $detail['contract_type'] = $summary_item['contract_type'];
            $detail['financed_amount'] = $summary_item['financed_amount'];
            $detail['transaction_type'] = DomainLists::TRANSACTION_TYPE_DOMAIN[$getField($contract, 'field_transaction_type')] ?: '—';
            $detail['currency'] = $getField($contract, 'field_currency') ?: $getField($contract, 'field_original_currency') ?: '—';
            $detail['start_date'] = $summary_item['start_date'];
            $detail['phase'] = $summary_item['contract_phase'] ?: '—';
            $detail['end_date'] = $summary_item['end_date'];
            $detail['last_update'] = $summary_item['last_update'];

            $granted = [];
            $granted['monthly_amount'] = $getField($contract, 'field_monthly_payment_amount') ?: '';
            $granted['periodicity'] = $summary_item['payment_periodicity'];
            $granted['installments_number'] = $summary_item['installments_number'];
            $granted['next_payment_date'] = DateStringFormatter::formatDateString($getField($contract, 'field_next_payment_date') ?: '');
            $granted['last_payment_date'] = DateStringFormatter::formatDateString($getField($contract, 'field_last_payment_date') ?: '');

            $detail['granted'] = $granted;

            $detail['outstanding'] = [
                'payments_number' => $getField($contract, 'field_outstanding_payment_no') ?: '',
                'balance' => $getField($contract, 'field_outstanding_balance') ?: '',
            ];

            $detail['overdue'] = [
                'payments_number' => $getField($contract, 'field_overdue_payments_number') ?: '',
                'amount' => $getField($contract, 'field_overdue_payments_amount') ?: '',
                'days' => $getField($contract, 'field_overdue_days') ?: '',
            ];

            $result['contract_details'][] = $detail;
        }


        return $result;
    }

    public function generateMemberReportPdfContent(int $nid, string $format = 'Legal', string $orientation = 'P'): ?array
    {
        $data = $this->buildReportData($nid);
        if (empty($data)) {
            return null;
        }

        $theme_name = 'mass_specc_bootstrap_sass';
        $theme_rel_path = \Drupal::service('extension.list.theme')->getPath($theme_name);
        $logo_fs_path = DRUPAL_ROOT . '/' . $theme_rel_path . '/images/new-logo.png';
        $request = \Drupal::request();
        $base = $request->getSchemeAndHttpHost();
        $base_path = rtrim($base . $request->getBasePath(), '/');

        if (file_exists($logo_fs_path) && is_readable($logo_fs_path)) {
            $data['logo_file_uri'] = 'file://' . $logo_fs_path;
        } else {
            $data['logo_file_uri'] = $base_path . '/' . $theme_rel_path . '/images/new-logo.png';
        }

        $data['logo_abs_url'] = $base_path . '/' . $theme_rel_path . '/images/new-logo.png';
        $render_array = [
            '#theme' => 'member_credit_report',
            '#data' => $data,
            '#attached' => [
                'library' => ['cooperative/member_credit_report'],
            ],
        ];

        $html = (string) \Drupal::service('renderer')->renderRoot($render_array);

        $request = \Drupal::request();
        $base = $request->getSchemeAndHttpHost();
        $base_path = rtrim($base . $request->getBasePath(), '/');

        if (stripos($html, '<head') !== false) {
            $html = preg_replace(
                '/<head([^>]*)>/i',
                '<head$1><base href="' . $base_path . '/">',
                $html,
                1
            );
        } else {
            $html = '<base href="' . $base_path . '/">' . $html;
        }

        $css = '';
        $bodyHtml = $html;

        if (preg_match('/<head.*?>(.*?)<\/head>/is', $html, $headMatches)) {
            $headContent = $headMatches[1];

            if (preg_match_all('/<style.*?>(.*?)<\/style>/is', $headContent, $styleMatches)) {
                foreach ($styleMatches[1] as $styleBlock) {
                    $css .= "\n" . $styleBlock;
                }
            }

            if (preg_match_all('/<link[^>]+href=[\'"]([^\'"]+)[\'"][^>]*>/i', $headContent, $linkMatches)) {
                foreach ($linkMatches[1] as $href) {
                    if (strpos($href, 'http') === 0) {
                        $css .= "\n/* external: $href */\n";
                    } else {
                        $abs = rtrim($base_path, '/') . '/' . ltrim($href, '/');
                        $css .= "\n/* linked CSS: $abs */\n";
                    }
                }
            }

            $bodyHtml = preg_replace('/<head.*?<\/head>/is', '', $html, 1);
        }

        if (preg_match('/<body.*?>(.*)<\/body>/is', $bodyHtml, $bodyMatches)) {
            $bodyHtml = $bodyMatches[1];
        } else {
            $bodyHtml = preg_replace('/<\/?html[^>]*>/i', '', $bodyHtml);
        }

        $css = preg_replace('/@page\s*\{[^}]*margin[^}]*\}/is', '', $css);

        $file_system = \Drupal::service('file_system');
        $mpdf_subdir = 'temporary://mpdf_temp';

        $file_system->prepareDirectory($mpdf_subdir, FileSystemInterface::CREATE_DIRECTORY);

        $temp_dir = $file_system->realpath($mpdf_subdir);

        $mpdf = new Mpdf([
            'tempDir' => $temp_dir,
            'mode' => 'utf-8',
            'format' => $format,
            'orientation' => $orientation,
            'default_font' => 'dejavusans',
            'autoScriptToLang' => true,
            'autoLangToFont' => true,
            'margin_left' => 12,
            'margin_right' => 12,
            'margin_top' => 12,
            'margin_bottom' => 22,
        ]);

        $mpdf->SetTitle('Member Credit Report');
        $mpdf->SetAuthor('Your Site Name');

        $mpdf->SetAutoPageBreak(true, 22);
        $mpdf->defaultfooterline = 1;
        $mpdf->defaultfooterfontsize = 10;

        $mpdf->SetFooter('<strong>Confidential</strong>||Page {PAGENO} of {nb}');

        $footerHtml = '
        <table width="100%" style="border-top:1px solid #ccc; font-size:10px; color:#555; padding-top:4px;">
            <tr>
                <td width="50%" align="left"><strong>Confidential</strong></td>
                <td width="50%" align="right">Page {PAGENO} of {nb}</td>
            </tr>
        </table>
    ';
        $mpdf->SetHTMLFooter($footerHtml, 'O');
        $mpdf->SetHTMLFooter($footerHtml, 'E');

        if (!empty(trim($css))) {
            $mpdf->WriteHTML($css, HTMLParserMode::HEADER_CSS);
        }

        $mpdf->WriteHTML($bodyHtml, HTMLParserMode::HTML_BODY);

        $pdfContent = $mpdf->Output('', 'S');

        return [
            'content' => $pdfContent,
            'filename' => 'member_report_' . date('Ymd_His') . '.pdf',
        ];
    }

}
