<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;

class CooperativeCreateForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'mass_specc_cooperative_create_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
         $form['#attached']['library'][] = 'common/char-count';
        // Fetch articles for the entity reference field.
        $article_options = [];
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'article')
            ->accessCheck(FALSE)
            ->execute();
        if (!empty($nids)) {
            $nodes = Node::loadMultiple($nids);
            foreach ($nodes as $node) {
                $article_options[$node->id()] = $node->getTitle();
            }
        }

        $form['coop_code'] = [
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
        $form['cic_provider_code'] = [
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
        $form['cbs_branch_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CBS Branch Code'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 5,
            ],
            '#description' => [
                '#markup' => '<span class="char-counter">0/5</span>',
            ],
            "#maxlength" => 5,
        ];
        $form['coop_name'] = [
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
        $form['ho_address'] = [
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
        $form['no_of_employees'] = [
            '#type' => 'number',
            '#title' => $this->t('Number of Employees in Head Office'),
            '#min' => 0,
            '#required' => TRUE,
        ];
        $form['contact_person'] = [
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
        $form['coop_contact_number'] = [
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
        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
        ];
        $form['cda_registration_date'] = [
            '#type' => 'date',
            '#title' => $this->t('CDA Registration Date'),
            '#required' => TRUE,
        ];
        $form['cda_firm_size'] = [
            '#type' => 'number',
            '#title' => $this->t('CDA Firm Size'),
            '#required' => TRUE,
        ];
        $form['assigned_report_templates'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Report Templates'),
            '#options' => $article_options,
            '#required' => TRUE,
            '#multiple' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
        ];

       

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $values = $form_state->getValues();

        try {
            $node = Node::create([
                'type' => 'cooperative',
                'title' => $values['coop_name'],
                'field_coop_name' => $values['coop_name'],
                'field_coop_code' => $values['coop_code'],
                'field_cic_provider_code' => $values['cic_provider_code'],
                'field_cbs_branch_code' => $values['cbs_branch_code'],
                'field_ho_address' => $values['ho_address'],
                'field_no_of_employees' => $values['no_of_employees'],
                'field_contact_person' => $values['contact_person'],
                'field_coop_contact_number' => $values['coop_contact_number'],
                'field_email' => $values['email'],
                'field_cda_registration_date' => $values['cda_registration_date'],
                'field_cda_firm_size' => $values['cda_firm_size'],
                'field_assigned_report_templates' => $values['assigned_report_templates'],
                'status' => 1,
            ]);
            $node->save();

            \Drupal::messenger()->addMessage($this->t('Cooperative created successfully.'));
        } catch (\Exception $e) {
            \Drupal::messenger()->addError($this->t('Error: @message', [
                '@message' => $e->getMessage(),
            ]));
        }
    }

}