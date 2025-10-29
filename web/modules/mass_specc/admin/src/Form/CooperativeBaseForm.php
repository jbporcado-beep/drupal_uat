<?php
namespace Drupal\admin\Form;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\admin\Plugin\Validation\Constraint\AlphaNumericConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\PhMobileNumberConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\EmailConstraintValidator;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\field\Entity\FieldConfig;

abstract class CooperativeBaseForm extends FormBase
{

    /**
     * Builds the cooperative form fields.
     */
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
    protected function buildCooperativeForm(array &$form, FormStateInterface $form_state, $existing_coop = NULL)
    {
        $form['#attached']['library'][] = 'common/char-count';
        $form['#attached']['library'][] = 'common/contact-number';

        $article_options = [];
        $nids = \Drupal::entityQuery('node')
            ->condition('type', ['report', 'report_template'], 'IN')
            ->accessCheck(FALSE)
            ->execute();

        if (!empty($nids)) {
            $nodes = Node::loadMultiple($nids);
            foreach ($nodes as $node) {
                $article_options[$node->id()] = $node->getTitle();
            }
        }

        $form['#prefix'] = '<div id="coop-form-wrapper" class="coop-wrapper">';
        $form['#suffix'] = '</div>';

        $form['basic-details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label"></div>',
            '#suffix' => '</div>',
        ];

        $form['basic-details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['basic-details']['code_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['basic-details']['code_group']['coop_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cooperative Code'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 20,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/20</span>',
            ],
            "#maxlength" => 20,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['basic-details']['code_group']['cic_provider_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CIC Provider Code'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 8,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/8</span>',
            ],
            "#maxlength" => 8,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];
        unset($form['coop_code'], $form['cic_provider_code']);

        $form['basic-details']['name_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];
        $form['basic-details']['name_group']['coop_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name of Cooperative'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['basic-details']['name_group']['coop_acronym'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Acronym'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 10,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/10</span>',
            ],
            "#maxlength" => 10,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];


        $form['basic-details']['ho_employees']['no_of_employees'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of Employees in Head Office'),
            '#min' => 0,
            '#max' => 9999,
            '#attributes' => ['style' => 'width: 49.5%;'],
        ];

        $form['basic-details']['cda_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['basic-details']['cda_group']['cda_registration_date'] = [
            '#type' => 'date',
            '#title' => $this->t('CDA Registration Date'),
            '#max' => date('Y-m-d'),
        ];

        $form['basic-details']['cda_group']['cda_firm_size'] = [
            '#type' => 'number',
            '#title' => $this->t('CDA Firm Size'),
            '#min' => 0,
            '#max' => 9999,
        ];

        $form['address_details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label">Address Information</div>',
            '#suffix' => '</div>',
        ];

        $form['address_details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['address_details']['street_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['address_details']['street_group']['street_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Street No. and Name'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['address_details']['street_group']['postal_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Postal Code'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['address_details']['purok_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['address_details']['purok_group']['subdivision'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Subdivision/Purok'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['address_details']['purok_group']['barangay'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Barangay'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['address_details']['city_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];
        $form['address_details']['city_group']['city'] = [
            '#type' => 'textfield',
            '#title' => $this->t('City'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];
        $form['address_details']['city_group']['province'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Province'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['address_details']['country'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Country'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
                'style' => 'width: 49.5%;'
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['contact_details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label">Contact Information</div>',
            '#suffix' => '</div>',
        ];

        $form['contact_details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['contact_details']['contact_person_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];


        $form['contact_details']['contact_person_group']['contact_person'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Person'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['contact_details']['contact_person_group']['coop_contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Number'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-numeric-only'],
                'data-maxlength' => 11,
                'maxlength' => 11,
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ],
            '#description' => $this->t('Format: 09XXXXXXXXX'),
            "#maxlength" => 11,
            '#element_validate' => [
                [PhMobileNumberConstraintValidator::class, 'validate'],
            ],
        ];

        $form['contact_details']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email Address'),
            '#required' => TRUE,
            '#attributes' => ['style' => 'width: 49.5%;'],
            '#element_validate' => [
                [EmailConstraintValidator::class, 'validate'],
            ],
        ];

        $form['other_details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label">Other Information</div>',
            '#suffix' => '</div>',
        ];

        $form['other_details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['other_details']['manager_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['other_details']['manager_group']['manager'] = [
            '#type' => 'textfield',
            '#title' => $this->t('General Manager/CEO'),
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['other_details']['manager_group']['manage_contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('General Manager/CEO Contact No.'),
            '#attributes' => [
                'class' => ['js-numeric-only'],
                'data-maxlength' => 11,
                'maxlength' => 11,
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ],
            '#description' => $this->t('Format: 09XXXXXXXXX'),
            "#maxlength" => 11,
            '#element_validate' => [
                [PhMobileNumberConstraintValidator::class, 'validate'],
            ],
        ];

        $form['other_details']['site_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['other_details']['site_group']['coop_website'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Coop Website'),
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/100</span>',
            ],
            "#maxlength" => 100,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['other_details']['site_group']['coop_tin'] = [
            '#type' => 'textfield',
            '#title' => $this->t('TIN Number'),
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 12,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/12</span>',
            ],
            "#maxlength" => 12,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $bundle = $existing_coop ? $existing_coop->bundle() : 'cooperative';
        $options = $this->getListFieldOptions('field_coop_type', 'node', $bundle);

        $options = ['' => $this->t('- Select Type -')] + $options;

        $form['other_details']['coop_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Type of Cooperative'),
            '#options' => $options,
            '#attributes' => [
                'class' => ['form-select', 'chosen-enable'],
                'style' => 'width: 49.5%;'
            ],
            '#wrapper_attributes' => [
                'style' => 'width:49.5%; display:inline-block; vertical-align:top;',
                'class' => ['form-half-wrapper'],
            ],
        ];

        $form['other_details']['branches_and_members'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['other_details']['branches_and_members']['no_of_branches'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of Branches'),
            '#min' => 0,
            '#max' => 9999,
        ];

        $form['other_details']['branches_and_members']['no_of_members'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of Members'),
            '#min' => 0,
            '#max' => 9999,
        ];

        $form['other_details']['assigned_report_templates'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Report Templates'),
            '#options' => $article_options,
            '#multiple' => TRUE,
            '#attributes' => [
                'class' => ['form-select', 'chosen-enable'],
                'data-placeholder' => $this->t('Search and select templates...'),
            ],
            '#wrapper_attributes' => [
                'style' => 'width:49.5%; display:inline-block; vertical-align:top;',
                'class' => ['form-half-wrapper'],
            ],
        ];

        $form['ftp_details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label">FTP Information</div>',
            '#suffix' => '</div>',
        ];

        $form['ftp_details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['ftp_details']['ftps_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['row-fields']],
        ];

        $form['ftp_details']['ftps_group']['ftps_username'] = [
            '#type' => 'textfield',
            '#title' => $this->t('FTPS Username'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 50,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/50</span>',
            ],
            "#maxlength" => 50,
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['ftp_details']['ftps_group']['ftps_password'] = [
            '#type' => 'password',
            '#title' => $this->t('FTPS Password'),
            '#required' => $existing_coop === NULL,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 50,
                'autocomplete' => 'new-password',
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/50</span>',
            ],
            "#maxlength" => 50,
        ];

        unset(
            $form['coop_code'],
            $form['cic_provider_code'],
            $form['coop_name'],
            $form['coop_acronym'],
            $form['no_of_employees'],
            $form['cda_registration_date'],
            $form['cda_firm_size'],
            $form['street_name'],
            $form['postal_code'],
            $form['subdivision'],
            $form['barangay'],
            $form['city'],
            $form['province'],
            $form['country'],
            $form['contact_person'],
            $form['coop_contact_number'],
            $form['email'],
            $form['manager'],
            $form['manage_contact_number'],
            $form['coop_website'],
            $form['coop_tin'],
            $form['coop_type'],
            $form['no_of_branches'],
            $form['no_of_members'],
            $form['assigned_report_templates'],
            $form['ftps_username'],
            $form['ftps_password']
        );

        if ($existing_coop) {
            if (isset($form['basic-details']['code_group']['coop_code'])) {
                if ($existing_coop->hasField('field_coop_code')) {
                    $form['basic-details']['code_group']['coop_code']['#default_value'] =
                        $existing_coop->get('field_coop_code')->value;
                }
            }

            if (isset($form['basic-details']['code_group']['cic_provider_code'])) {
                if ($existing_coop->hasField('field_cic_provider_code')) {
                    $form['basic-details']['code_group']['cic_provider_code']['#default_value'] =
                        $existing_coop->get('field_cic_provider_code')->value;
                }
            }

            if (isset($form['basic-details']['name_group']['coop_name'])) {
                if ($existing_coop->hasField('field_coop_name')) {
                    $form['basic-details']['name_group']['coop_name']['#default_value'] =
                        $existing_coop->get('field_coop_name')->value;
                }
            }

            if (isset($form['basic-details']['name_group']['coop_acronym'])) {
                if ($existing_coop->hasField('field_coop_acronym')) {
                    $form['basic-details']['name_group']['coop_acronym']['#default_value'] =
                        $existing_coop->get('field_coop_acronym')->value;
                }
            }

            if (isset($form['basic-details']['ho_employees']['no_of_employees'])) {
                if ($existing_coop->hasField('field_no_of_employees')) {
                    $form['basic-details']['ho_employees']['no_of_employees']['#default_value'] =
                        $existing_coop->get('field_no_of_employees')->value;
                }
            }

            if (isset($form['basic-details']['cda_group']['cda_registration_date'])) {
                if ($existing_coop->hasField('field_cda_registration_date')) {
                    $form['basic-details']['cda_group']['cda_registration_date']['#default_value'] =
                        $existing_coop->get('field_cda_registration_date')->value;
                }
            }

            if (isset($form['basic-details']['cda_group']['cda_firm_size'])) {
                if ($existing_coop->hasField('field_cda_firm_size')) {
                    $form['basic-details']['cda_group']['cda_firm_size']['#default_value'] =
                        $existing_coop->get('field_cda_firm_size')->value;
                }
            }

            if (isset($form['address_details']['street_group']['street_name'])) {
                if ($existing_coop->hasField('field_street_no_and_name')) {
                    $form['address_details']['street_group']['street_name']['#default_value'] =
                        $existing_coop->get('field_street_no_and_name')->value;
                }
            }
            if (isset($form['address_details']['street_group']['postal_code'])) {
                if ($existing_coop->hasField('field_postal_code')) {
                    $form['address_details']['street_group']['postal_code']['#default_value'] =
                        $existing_coop->get('field_postal_code')->value;
                }
            }
            if (isset($form['address_details']['purok_group']['subdivision'])) {
                if ($existing_coop->hasField('field_subdivision_purok')) {
                    $form['address_details']['purok_group']['subdivision']['#default_value'] =
                        $existing_coop->get('field_subdivision_purok')->value;
                }
            }
            if (isset($form['address_details']['purok_group']['barangay'])) {
                if ($existing_coop->hasField('field_barangay')) {
                    $form['address_details']['purok_group']['barangay']['#default_value'] =
                        $existing_coop->get('field_barangay')->value;
                }
            }
            if (isset($form['address_details']['city_group']['city'])) {
                if ($existing_coop->hasField('field_city')) {
                    $form['address_details']['city_group']['city']['#default_value'] =
                        $existing_coop->get('field_city')->value;
                }
            }
            if (isset($form['address_details']['city_group']['province'])) {
                if ($existing_coop->hasField('field_province')) {
                    $form['address_details']['city_group']['province']['#default_value'] =
                        $existing_coop->get('field_province')->value;
                }
            }
            if (isset($form['address_details']['country'])) {
                if ($existing_coop->hasField('field_country')) {
                    $form['address_details']['country']['#default_value'] =
                        $existing_coop->get('field_country')->value;
                }
            }

            if (isset($form['contact_details']['contact_person_group']['contact_person'])) {
                if ($existing_coop->hasField('field_contact_person')) {
                    $form['contact_details']['contact_person_group']['contact_person']['#default_value'] =
                        $existing_coop->get('field_contact_person')->value;
                }
            }
            if (isset($form['contact_details']['contact_person_group']['coop_contact_number'])) {
                if ($existing_coop->hasField('field_coop_contact_number')) {
                    $form['contact_details']['contact_person_group']['coop_contact_number']['#default_value'] =
                        $existing_coop->get('field_coop_contact_number')->value;
                }
            }
            if (isset($form['contact_details']['email'])) {
                if ($existing_coop->hasField('field_email')) {
                    $form['contact_details']['email']['#default_value'] =
                        $existing_coop->get('field_email')->value;
                }
            }

            if (isset($form['other_details']['manager_group']['manager'])) {
                if ($existing_coop->hasField('field_general_manager')) {
                    $form['other_details']['manager_group']['manager']['#default_value'] =
                        $existing_coop->get('field_general_manager')->value;
                }
            }
            if (isset($form['other_details']['manager_group']['manage_contact_number'])) {
                if ($existing_coop->hasField('field_general_manager_contact_no')) {
                    $form['other_details']['manager_group']['manage_contact_number']['#default_value'] =
                        $existing_coop->get('field_general_manager_contact_no')->value;
                }
            }
            if (isset($form['other_details']['site_group']['coop_website'])) {
                if ($existing_coop->hasField('field_cooperative_website_url')) {
                    $form['other_details']['site_group']['coop_website']['#default_value'] =
                        $existing_coop->get('field_cooperative_website_url')->value;
                }
            }
            if (isset($form['other_details']['site_group']['coop_tin'])) {
                if ($existing_coop->hasField('field_cooperative_tin')) {
                    $form['other_details']['site_group']['coop_tin']['#default_value'] =
                        $existing_coop->get('field_cooperative_tin')->value;
                }
            }
            if (isset($form['other_details']['coop_type'])) {
                if ($existing_coop->hasField('field_coop_type')) {
                    $form['other_details']['coop_type']['#default_value'] =
                        $existing_coop->get('field_coop_type')->value;
                }
            }
            if (isset($form['other_details']['branches_and_members']['no_of_branches'])) {
                if ($existing_coop->hasField('field_number_of_branches')) {
                    $form['other_details']['branches_and_members']['no_of_branches']['#default_value'] =
                        $existing_coop->get('field_number_of_branches')->value;
                }
            }
            if (isset($form['other_details']['branches_and_members']['no_of_members'])) {
                if ($existing_coop->hasField('field_number_of_members')) {
                    $form['other_details']['branches_and_members']['no_of_members']['#default_value'] =
                        $existing_coop->get('field_number_of_members')->value;
                }
            }

            if (isset($form['other_details']['assigned_report_templates'])) {
                if ($existing_coop->hasField('field_assigned_report_templates')) {
                    $form['other_details']['assigned_report_templates']['#default_value'] =
                        array_column($existing_coop->get('field_assigned_report_templates')->getValue(), 'target_id');
                }
            }

            if (isset($form['ftp_details']['ftps_group']['ftps_username'])) {
                if ($existing_coop->hasField('field_ftps_username')) {
                    $form['ftp_details']['ftps_group']['ftps_username']['#default_value'] =
                        $existing_coop->get('field_ftps_username')->value;
                }
            }
            if (isset($form['ftp_details']['ftps_group']['ftps_password'])) {
                $form['ftp_details']['ftps_group']['ftps_password']['#description'] = [
                    '#markup' => '<span>' . $this->t('Leave blank to keep the existing password.') . '</span>',
                ];
            }
        }

    }

    /**
     * Shared validation logic for cooperative forms.
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        parent::validateForm($form, $form_state);

        $coop_id = $form_state->getValue('coop_id');
        $coop_name = trim($form_state->getValue('coop_name'));
        $coop_code = trim($form_state->getValue('coop_code'));
        $cic_code = trim($form_state->getValue('cic_provider_code'));
        $cda_registration_date = $form_state->getValue('cda_registration_date');


        if (!empty($cda_registration_date)) {
            $selected_date = strtotime($cda_registration_date);
            $today = strtotime(date('Y-m-d'));
            if ($selected_date > $today) {
                $form_state->setErrorByName('cda_registration_date', $this->t('The CDA Registration Date cannot be in the future.'));
            }
        }

        if ($coop_code && strlen($coop_code) !== 10) {
            $form_state->setErrorByName('coop_code', $this->t('Cooperative code must be 10 characters'));
        }

        if ($cic_code && strlen($cic_code) !== 8) {
            $form_state->setErrorByName('cic_provider_code', $this->t('CIC Provider code must be 8 characters'));
        }

        if ($coop_name) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'cooperative')
                ->condition('field_coop_name', $coop_name);
            if ($coop_id) {
                $query->condition('nid', $coop_id, '<>');
            }
            $existing = $query->accessCheck(FALSE)->execute();
            if (!empty($existing)) {
                $form_state->setErrorByName('coop_name', $this->t('The Cooperative Name %name is already in use.', [
                    '%name' => $coop_name,
                ]));
            }
        }

        if ($coop_code) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'cooperative')
                ->condition('field_coop_code', $coop_code);
            if ($coop_id) {
                $query->condition('nid', $coop_id, '<>');
            }
            $existing = $query->accessCheck(FALSE)->execute();
            if (!empty($existing)) {
                $form_state->setErrorByName('coop_code', $this->t('The Cooperative Code %code is already in use.', [
                    '%code' => $coop_code,
                ]));
            }
        }

        if ($cic_code) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'cooperative')
                ->condition('field_cic_provider_code', $cic_code);
            if ($coop_id) {
                $query->condition('nid', $coop_id, '<>');
            }

            $existing = $query->accessCheck(FALSE)->execute();

            if (!empty($existing)) {
                $form_state->setErrorByName('cic_provider_code', $this->t('The CIC Provider Code %code is already in use.', [
                    '%code' => $cic_code,
                ]));
            }
        }
    }

    protected function getListFieldOptions(string $field_name, string $entity_type = 'node', string $bundle = 'cooperative'): array
    {
        $field = FieldConfig::loadByName($entity_type, $bundle, $field_name);
        if (!$field) {
            return [];
        }

        $settings = $field->getSettings();

        if (!empty($settings['allowed_values']) && is_array($settings['allowed_values'])) {
            return $settings['allowed_values'];
        }

        if (!empty($settings['allowed_values_function']) && is_callable($settings['allowed_values_function'])) {
            $fn = $settings['allowed_values_function'];
            $values = call_user_func($fn, $field);
            if (is_array($values)) {
                return $values;
            }
        }

        return [];
    }
}