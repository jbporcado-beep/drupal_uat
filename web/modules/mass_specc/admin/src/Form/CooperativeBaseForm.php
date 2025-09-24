<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

abstract class CooperativeBaseForm extends FormBase {

    /**
     * Builds the cooperative form fields.
     */
    protected function buildCooperativeForm(array &$form, FormStateInterface $form_state, $existing_coop = NULL) {
        $form['#attached']['library'][] = 'common/char-count';

        $article_options = [];
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'report')
            ->accessCheck(FALSE)
            ->execute();
        if (!empty($nids)) {
            $nodes = Node::loadMultiple($nids);
            foreach ($nodes as $node) {
                $article_options[$node->id()] = $node->getTitle();
            }
        }

        $form['main_grid'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['coop-main-grid'] ],
        ];

        $form['coop_main_grid']['left_col'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['coop-main-left']],
        ];

        $form['main_grid']['left_col']['code_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['coop-code-group'] ],
        ];

        $form['main_grid']['left_col']['code_group']['coop_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cooperative Code'),
            '#required' => TRUE,
            '#attributes' => [
            'class' => ['js-char-count'],
            'data-maxlength' => 50,
            ],
            '#description' => [
            '#markup' => '<span class="char-counter">0/50</span>',
            ],
            "#maxlength" => 50,
        ];

        $form['main_grid']['left_col']['code_group']['cic_provider_code'] = [
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
        ];
        unset($form['coop_code'], $form['cic_provider_code']);

        $form['main_grid']['right_col'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['coop-main-right']],
        ];

        $form['main_grid']['right_col']['coop_name'] = [
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
        ];

        $form['main_grid']['right_col']['head_office_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['coop-head-office-group'] ],
        ];

        $form['main_grid']['right_col']['head_office_group']['ho_address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Head Office Address'),
            '#required' => TRUE,
            '#attributes' => [
            'class' => ['js-char-count'],
            'data-maxlength' => 255,
            ],
            '#description' => [
            '#markup' => '<span class="char-counter">0/255</span>',
            ],
            '#maxlength' => 255,
        ];

        $form['main_grid']['right_col']['head_office_group']['no_of_employees'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of Employees in Head Office'),
            '#min' => 0,
            '#required' => TRUE,
        ];

        $form['main_grid']['right_col']['contact_person'] = [
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
        ];

        $form['main_grid']['right_col']['coop_contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Number'),
            '#required' => TRUE,
            '#attributes' => [
            'class' => ['js-char-count'],
            'data-maxlength' => 13,
            ],
            '#description' => [
            '#markup' => '<span class="char-counter">0/13</span>',
            ],
            "#maxlength" => 13,
        ];

        $form['main_grid']['right_col']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
        ];

        $form['main_grid']['right_col']['cda_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['coop-cda-group'] ],
        ];

        $form['main_grid']['right_col']['cda_group']['cda_registration_date'] = [
            '#type' => 'date',
            '#title' => $this->t('CDA Registration Date'),
            '#required' => TRUE,
        ];

        $form['main_grid']['right_col']['cda_group']['cda_firm_size'] = [
            '#type' => 'number',
            '#title' => $this->t('CDA Firm Size'),
            '#required' => TRUE,
        ];

        $form['main_grid']['right_col']['assigned_report_templates'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Report Templates'),
            '#options' => $article_options,
            '#multiple' => TRUE,
            '#attributes' => [
                'class' => ['selectify-apply-searchable'],
            ],
            '#attached' => [
                'library' => [
                'selectify/selectify-base',
                'selectify/selectify-helper',
                'selectify/selectify-dropdowns',
                'selectify/selectify-dropdown-searchable',
                ],
            ],
        ];

        unset(
            $form['coop_name'],
            $form['ho_address'],
            $form['no_of_employees'],
            $form['contact_person'],
            $form['coop_contact_number'],
            $form['email'],
            $form['cda_registration_date'],
            $form['cda_firm_size'],
            $form['assigned_report_templates']
        );

        if ($existing_coop) {
            $form['main_grid']['left_col']['code_group']['coop_code']['#default_value'] = $existing_coop->get('field_coop_code')->value;
            $form['main_grid']['left_col']['code_group']['cic_provider_code']['#default_value'] = $existing_coop->get('field_cic_provider_code')->value;

            $form['main_grid']['right_col']['coop_name']['#default_value'] = $existing_coop->get('field_coop_name')->value;
            $form['main_grid']['right_col']['head_office_group']['ho_address']['#default_value'] = $existing_coop->get('field_ho_address')->value;
            $form['main_grid']['right_col']['head_office_group']['no_of_employees']['#default_value'] = $existing_coop->get('field_no_of_employees')->value;

            $form['main_grid']['right_col']['contact_person']['#default_value'] = $existing_coop->get('field_contact_person')->value;
            $form['main_grid']['right_col']['coop_contact_number']['#default_value'] = $existing_coop->get('field_coop_contact_number')->value;
            $form['main_grid']['right_col']['email']['#default_value'] = $existing_coop->get('field_email')->value;

            $form['main_grid']['right_col']['cda_group']['cda_registration_date']['#default_value'] = $existing_coop->get('field_cda_registration_date')->value;
            $form['main_grid']['right_col']['cda_group']['cda_firm_size']['#default_value'] = $existing_coop->get('field_cda_firm_size')->value;

            $form['main_grid']['right_col']['assigned_report_templates']['#default_value'] =
                array_column($existing_coop->get('field_assigned_report_templates')->getValue(), 'target_id');
        }
    }

    /**
     * Shared validation logic for cooperative forms.
     */
    public function validateForm(array &$form, FormStateInterface $form_state) {

        if (strlen($form_state->getValue('coop_code')) < 3) {
            $form_state->setErrorByName('coop_code', $this->t('Cooperative code must be at least 3 characters.'));
        }

    }
}