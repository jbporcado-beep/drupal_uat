<?php

namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;

class GeneralReportViewerForm extends FormBase
{
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
        'provider_subject_no' => 'Provider Subject Number'
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
            ];
            $form['layout']['dropdowns_wrapper']['branch_dropdown'] = [
                '#type' => 'select',
                '#title' => $this->t('Branch'),
                '#options' => $branch_options,
                '#default_value' => $branch->id(),
                '#attributes' => [
                    'class' => ['dropdown-item'],
                    'disabled' => 'disabled',
                ]
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

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
    {
        $coop_id = $form_state->getValue(['coop_dropdown']);
        $branch_id = $form_state->getValue(['branch_dropdown']);


        // Filter on branch or cooperative
        $individuals = [];
        if (!empty($branch_id)) {
            $branch_node = \Drupal::entityTypeManager()->getStorage('node')->load($branch_id);
            $branch_name = $branch_node->getTitle();
            $individuals = $this->getIndividuals($branch_name);
        } else if (!empty($coop_id)) {
            $branches = $this->getBranches($coop_id);
            foreach ($branches as $branch) {
                $branch_name = $branch->getTitle();
                $individuals = array_merge($individuals, $this->getIndividuals($branch_name));
            }
        } else {
            $individuals = $this->getAllIndividuals();
        }

        if (empty($individuals)) {
            \Drupal::messenger()->addError('No individuals found under the selected branch and/or cooperative.');
            return;
        }

        $individual_fields = $form_state->getValue(['individual']);
        $identification_fields = $form_state->getValue(['identification']);

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
                $header_row[] = 'field_ ' . $field;
            }
        }
        $rows[] = $header_row;


        foreach ($individuals as $individual) {
            $rows[] = $this->buildRow($individual_fields, $identification_fields, $individual);
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

    private function getIndividuals(string $branch_name): array
    {
        $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
            'type' => 'individual',
            'field_branch_code' => $branch_name,
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

    private function buildRow($individual_fields, $identification_fields, $individual)
    {
        $row = [];
        foreach ($individual_fields as $field) {
            $field = trim($field);
            if (array_key_exists($field, $this->individual_aggregrate_fields)) {
                foreach (($this->individual_aggregrate_fields)[$field] as $subfield) {
                    $row[] = '"' . $individual->get('field_' . $field)->entity->get($subfield)->value . '"';
                }
            } else if ($field !== "0") {
                $row[] = '"' . $individual->get('field_' . $field)->value . '"';
            }
        }
        foreach ($identification_fields as $field) {
            if (trim($field) !== "0") {
                $row[] = '"' . $individual->get('field_identification')->entity->get('field_' . $field)->value . '"';
            }
        }
        return $row;
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
