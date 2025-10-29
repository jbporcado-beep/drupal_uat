<?php

namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\node\Entity\Node;
use Symfony\Component\HttpFoundation\Response;

use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class GeneralReportViewerForm extends FormBase
{
    protected $currentUser;
    protected $activityLogger;
    public function __construct(UserActivityLogger $activityLogger, AccountProxyInterface $currentUser)
    {
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('admin.user_activity_logger'),
            $container->get('current_user')
        );
    }
    private $individual_field_labels = [
        'address' => 'Address',
        'branch_code' => 'Branch Code',
        'cars_owned' => 'Cars Owned',
        'civil_status' => 'Civil Status',
        'contact' => 'Contact',
        'country_of_birth_code' => 'Country of Birth Code',
        'date_of_birth' => 'Date of Birth',
        'employment' => 'Employment',
        'family' => 'Family',
        'first_name' => 'First Name',
        'middle_name' => 'Middle Name',
        'last_name' => 'Last Name',
        'suffix' => 'Suffix',
        'gender' => 'Gender',
        'nationality' => 'Nationality',
        'number_of_dependents' => 'Number of Dependents',
        'place_of_birth' => 'Place of Birth',
        'previous_last_name' => 'Previous Last Name',
        'provider_code' => 'Provider Code',
        'provider_subject_no' => 'Provider Subject Number',
        'resident' => 'Resident',
        'title' => 'Title',
    ];

    private $identification_field_labels = [
        'id1_expirydate' => 'ID: 1 Expiry Date',
        'id1_issuecountry' => 'ID: 1 Issue Country',
        'id1_issuedate' => 'ID: 1 Issue Date',
        'id1_issuedby' => 'ID: 1 Issued By',
        'id1_number' => 'ID: 1 Number',
        'id1_type' => 'ID: 1 Type',
        'id2_expirydate' => 'ID: 2 Expiry Date',
        'id2_issuecountry' => 'ID: 2 Issue Country',
        'id2_issuedate' => 'ID: 2 Issue Date',
        'id2_issuedby' => 'ID: 2 Issued By',
        'id2_number' => 'ID: 2 Number',
        'id2_type' => 'ID: 2 Type',
        'identification1_number' => 'Identification 1: Number',
        'identification1_type' => 'Identification 1: Type',
        'identification2_number' => 'Identification 2: Number',
        'identification2_type' => 'Identification 2: Type',
    ];

    private $installment_contract_field_labels = [
        'contract_end_actual_date' => 'Contract End Actual Date',
        'contract_end_planned_date' => 'Contract End Planned Date',
        'contract_phase' => 'Contract Phase',
        'contract_start_date' => 'Contract Start Date',
        'contract_type' => 'Contract Type',
        'currency' => 'Currency',
        'payment_periodicity' => 'Payment Periodicity',
        'financed_amount' => 'Financed Amount',
        'reference_date' => 'Reference Date',
        'submission_type' => 'Submission Type',
        'version' => 'Version',
        'installments_no' => 'Installments Number',
        'last_payment_amount' => 'Last Payment Amount',
        'monthly_payment_amount' => 'Monthly Payment Amount',
        'next_payment_date' => 'Next Payment Date',
        'original_currency' => 'Original Currency',
        'outstanding_balance' => 'Outstanding Balance',
        'outstanding_payment_no' => 'Outstanding Payment Number',
        'overdue_days' => 'Overdue Days',
        'overdue_payments_amount' => 'Overdue Payments Amount',
        'overdue_payments_number' => 'Overdue Payments Number',
        'provider_contract_no' => 'Provider Contract Number',
        'role' => 'Role',
    ];

    private $header_fields = [
        'reference_date',
        'submission_type',
        'version',
    ];

    private $individual_aggregrate_fields = [
        'address' => [
            'field_address1_fulladdress',
            'field_address1_type',
            'field_address2_fulladdress',
            'field_address2_type'
        ],
        'contact' => [
            'field_contact1_type',
            'field_contact1_value',
            'field_contact2_type',
            'field_contact2_value'
        ],
        'employment' => [
            'field_employ_occupation',
            'field_employ_occupation_status',
            'field_employ_psic',
            'field_employ_trade_name'
        ],
        'family' => [
            'field_father_first_name',
            'field_father_last_name',
            'field_father_middle_name',
            'field_father_suffix',
            'field_mother_maiden_full_name',
            'field_spouse_first_name',
            'field_spouse_last_name',
            'field_spouse_middle_name'
        ]
    ];

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'mass_specc_general_report_viewer_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state, $user = NULL)
    {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/report-styles';
        $form['#attached']['library'][] = 'admin/general_report_viewer';

        $form['layout'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['form-page-layout']],
        ];

        // Cooperative and Branch dropdown
        $form['layout']['dropdowns_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dropdowns-wrapper']],
        ];

        $uid = \Drupal::currentUser()->id();
        $user = $uid > 0 ? User::load($uid) : NULL;
        if ($user->hasRole('mass_specc_admin')) {
            $coop_options = $this->getCooperatives();

            $form['layout']['dropdowns_wrapper']['coop_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Cooperative'),
                '#empty_option' => $this->t('- Select a cooperative -'),
                '#options' => $coop_options,
                '#attributes' => ['class' => ['dropdown-item']],
                '#ajax' => [
                    'callback' => '::updateBranchField',
                    'event' => 'change',
                    'wrapper' => 'branch-dropdown-wrapper',
                    'progress' => ['type' => 'throbber', 'message' => NULL],
                ],
                '#default_value' => '',
                '#sort_options' => TRUE,
                '#chosen' => TRUE,
            ];
            $form['layout']['dropdowns_wrapper']['branch_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Branch'),
                '#empty_option' => $this->t('- Select a branch -'),
                '#prefix' => '<div id="branch-dropdown-wrapper">',
                '#suffix' => '</div>',
                '#attributes' => [
                    'class' => ['dropdown-item'],
                    'disabled' => 'disabled',
                ],
                '#validated' => TRUE,
                '#sort_order' => TRUE,
                '#chosen' => TRUE,
            ];
        } else if ($user->hasRole('access')) {
            $coop_entity = $user->get('field_cooperative')->referencedEntities();
            $coop = reset($coop_entity);
            $coop_options = [$coop->id() => $coop->getTitle()];

            $branch_entity = $user->get('field_branch')->referencedEntities();
            $branch = reset($branch_entity);
            $branch_options = [$branch->id() => $branch->getTitle()];

            $form['layout']['dropdowns_wrapper']['coop_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Cooperative'),
                '#options' => $coop_options,
                '#default_value' => $coop->id(),
                '#attributes' => [
                    'class' => ['dropdown-item'],
                    'disabled' => 'disabled',
                ],
                '#chosen' => TRUE,
            ];
            $form['layout']['dropdowns_wrapper']['branch_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Branch'),
                '#options' => $branch_options,
                '#default_value' => $branch->id(),
                '#attributes' => [
                    'class' => ['dropdown-item'],
                    'disabled' => 'disabled',
                ],
                '#chosen' => TRUE,
            ];
        }

        // Submit button
        $form['layout']['dropdowns_wrapper']['actions'] = ['#type' => 'actions'];
        $form['layout']['dropdowns_wrapper']['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Download'),
            '#attributes' => [
                'disabled' => 'disabled',
            ]
        ];

        // Field selection
        $form['layout']['selection_wrapper'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['dropdowns-wrapper']],
        ];
        $form['layout']['selection_wrapper']['label'] = [
            '#type' => 'markup',
            '#markup' => '<b>' . $this->t('Fields') . '</b>',
        ];
        $form['layout']['selection_wrapper']['selection_checkbox_wrapper'] = [
            '#type' => 'container',
            '#prefix' => '<div id="checkbox-wrapper">',
            '#suffix' => '</div>',
        ];
        $form['layout']['selection_wrapper']['selection_checkbox_wrapper']['select_all'] = [
            '#type' => 'checkbox',
            '#title' => $this->t('Select all'),
            '#attributes' => [
                'class' => ['content-type-select-all'],
            ],
        ];

        // "Individual" selection
        $form['layout']['selection_wrapper']['selection_checkbox_wrapper']['individual'] = [
            '#type' => 'checkboxes',
            '#options' => $this->individual_field_labels,
            '#title' => $this->t('Individual'),
            '#attributes' => [
                'class' => ['field-checkbox-selection'],
            ],
        ];

        // "Identification" selection
        $form['layout']['selection_wrapper']['selection_checkbox_wrapper']['identification'] = [
            '#type' => 'checkboxes',
            '#options' => $this->identification_field_labels,
            '#title' => $this->t('Identities'),
            '#attributes' => [
                'class' => ['field-checkbox-selection'],
            ],
        ];

        // "Installment Contract" selection
        $form['layout']['selection_wrapper']['selection_checkbox_wrapper']['installment_contract'] = [
            '#type' => 'checkboxes',
            '#options' => $this->installment_contract_field_labels,
            '#title' => $this->t('Installment Contracts'),
            '#attributes' => [
                'class' => ['field-checkbox-selection'],
            ],
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $coop_id = $form_state->getValue(['coop_dropdown']);
        $branch_id = $form_state->getValue(['branch_dropdown']);

        // Filter on branch or cooperative
        $individuals = [];
        if (!empty($branch_id)) {
            $branch_node = \Drupal::entityTypeManager()->getStorage('node')->load($branch_id);
            $branch_code = $branch_node->get('field_branch_code')->value;
            $individuals = $this->getIndividuals($branch_code);
        } else if (!empty($coop_id)) {
            $branches = $this->getBranches($coop_id);
            foreach ($branches as $branch) {
                $branch_code = $branch->get('field_branch_code')->value;
                $individuals = array_merge($individuals, $this->getIndividuals($branch_code));
            }
        } else {
            $individuals = $this->getAllIndividuals();
        }

        if (empty($individuals)) {
            \Drupal::messenger()->addError('No individuals found under the selected branch and/or cooperative.');
            return;
        }

        $individual_to_installment_contracts = [];
        foreach ($individuals as $individual) {
            $individual_to_installment_contracts[$individual->id()] = $this->getInstallmentContracts($individual->id());
        }

        $individual_fields = $form_state->getValue(['individual']);
        $identification_fields = $form_state->getValue(['identification']);
        $installment_contract_fields = $form_state->getValue(['installment_contract']);

        // Build header row of CSV
        $rows = [];
        $header_row = [];
        foreach ($individual_fields as $field) {
            $field = trim($field);
            if (array_key_exists($field, $this->individual_aggregrate_fields)) {
                $header_row = array_merge($header_row, $this->individual_aggregrate_fields[$field]);
            } else if ($field !== "0") {
                $header_row[] = 'field_' . $field;
            }
        }
        foreach ($identification_fields as $field) {
            if (trim($field) !== "0") {
                $header_row[] = 'field_' . $field;
            }
        }
        foreach ($installment_contract_fields as $field) {
            if (trim($field) !== "0") {
                $header_row[] = 'field_' . $field;
            }
        }

        $rows[] = $header_row;

        foreach ($individuals as $individual) {
            $rows = array_merge($rows, $this->buildRows(
                $individual_fields,
                $identification_fields,
                $installment_contract_fields,
                $individual,
                $individual_to_installment_contracts[$individual->id()]
            ));
        }

        $csv_content = '';
        foreach ($rows as $row) {
            $cleaned_row = str_replace('""', '', join(",", $row));
            $csv_content .= $cleaned_row . "\n";
        }

        $response = new Response($csv_content);
        $response->headers->set('Content-Type', 'text/csv');
        $response->headers->set('Content-Disposition', 'attachment; filename="report.csv"');
        $response->send();


        $data = [
            'changed_fields' => [],
            'performed_by_name' => $this->currentUser->getDisplayName(),
        ];

        $coop_name = '';
        $branch_name = '';

        if (!empty($coop_id)) {
            $coop_node = Node::load($coop_id);
            $coop_name = $coop_node ? $coop_node->getTitle() : '';
        }

        if (!empty($branch_id)) {
            $branch_node = Node::load($branch_id);
            $branch_name = $branch_node ? $branch_node->getTitle() : '';
        }

        if ($coop_name && $branch_name) {
            $action = 'Generated database report for ' . $coop_name . ' - ' . $branch_name;
        } elseif ($coop_name) {
            $action = 'Generated database report for ' . $coop_name;
        } else {
            $action = 'Generated database report for ALL cooperatives';
        }

        $this->activityLogger->log($action, 'node', NULL, $data, NULL, $this->currentUser);

        exit;
    }

    private function getCooperatives(): array
    {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $options = [];
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'cooperative')
            ->accessCheck(TRUE);
        $nids = $query->execute();
        if (!empty($nids)) {
            $nodes = $node_storage->loadMultiple($nids);
            foreach ($nodes as $node) {
                $options[$node->id()] = $node->getTitle();
            }
        }
        return $options;
    }

    private function getIndividuals(string $branch_code): array
    {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'individual',
            'field_branch_code' => $branch_code,
        ]);
        return $nodes;
    }

    private function getAllIndividuals(): array
    {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'individual',
        ]);
        return $nodes;
    }

    private function getBranches(string $coop_id): array
    {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'branch',
            'field_branch_coop' => $coop_id,
        ]);

        return $nodes;
    }

    private function getInstallmentContracts(string $individual_id): array
    {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'installment_contract',
            'field_subject' => $individual_id,
        ]);
        return $nodes;
    }

    private function buildRows(
        $individual_fields,
        $identification_fields,
        $installment_contract_fields,
        $individual,
        $installment_contracts
    ) {
        $rows = [];

        $individual_row = [];
        foreach ($individual_fields as $field) {
            $field = trim($field);
            if (array_key_exists($field, $this->individual_aggregrate_fields)) {
                foreach (($this->individual_aggregrate_fields)[$field] as $subfield) {
                    $referenced_entity = $individual->get('field_' . $field)->entity;
                    $individual_row[] = '"' . $referenced_entity?->get($subfield)->value . '"';
                }
            } else if ($field !== "0") {
                $individual_row[] = '"' . $individual->get('field_' . $field)->value . '"';
            }
        }
        foreach ($identification_fields as $field) {
            if (trim($field) !== "0") {
                $referenced_entity = $individual->get('field_identification')->entity;
                $individual_row[] = '"' . $referenced_entity?->get('field_' . $field)->value . '"';
            }
        }

        // check if no contract fields chosen
        foreach($installment_contract_fields as $field) {
            if (trim($field) !== "0") {
                break;
            }
            return [$individual_row];
        }

        // individual has no installment contracts
        if (sizeof($installment_contracts) == 0) {
            $empty_installment_contract_cells = [];
            foreach ($installment_contract_fields as $field) {
                if (trim($field) !== "0") {
                    $empty_installment_contract_cells[] = '""';
                }
            }

            return [array_merge($individual_row, $empty_installment_contract_cells)]; // single-element array
        }

        // individual has installment contracts; join with Installment Contracts entity
        $installment_contract_rows = [];
        foreach ($installment_contracts as $installment_contract) {
            $installment_contract_row = [];
            foreach ($installment_contract_fields as $field) {
                $field = trim($field);
                if (in_array($field, $this->header_fields)) {
                    $referenced_entity = $installment_contract->get('field_header')->entity;
                    $installment_contract_row[] = '"' . $referenced_entity?->get('field_' . $field)->value . '"';
                } else if ($field !== "0") {
                    $installment_contract_row[] = '"' . $installment_contract->get('field_' . $field)->value . '"';
                }
            }
            $installment_contract_rows[] = $installment_contract_row;
        }

        foreach ($installment_contract_rows as $installment_contract_row) {
            $rows[] = array_merge($individual_row, $installment_contract_row);
        }

        return $rows;
    }

    public function updateBranchField(array &$form, FormStateInterface $form_state)
    {
        $coop_id = $form_state->getValue(['coop_dropdown']);
        $options = ['' => $this->t('- Select a branch -')];

        if (!empty($coop_id)) {
            $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
                'type' => 'branch',
                'field_branch_coop' => $coop_id,
            ]);
            foreach ($nodes as $node) {
                $options[$node->id()] = $node->getTitle();
            }
        } else {
            $form['layout']['dropdowns_wrapper']['branch_dropdown']['#attributes']['disabled'] = 'disabled';
            $form['layout']['dropdowns_wrapper']['branch_dropdown']['#default_value'] = '';
        }

        if (sizeof($options) > 1) {
            unset($form['layout']['dropdowns_wrapper']['branch_dropdown']['#attributes']['disabled']);
        }

        $form['layout']['dropdowns_wrapper']['branch_dropdown']['#options'] = $options;

        return $form['layout']['dropdowns_wrapper']['branch_dropdown'];
    }
}
