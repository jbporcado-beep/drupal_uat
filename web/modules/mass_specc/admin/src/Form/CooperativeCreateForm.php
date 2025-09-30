<?php
namespace Drupal\admin\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Url;
class CooperativeCreateForm extends CooperativeBaseForm {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'mass_specc_cooperative_create_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state) {

        $form['#title'] = $this->t('Add New Cooperative');

        $form['header'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['form-header'],
            ],
            'back' => [
                '#markup' => '<a href="' . Url::fromRoute('cooperative.list')->toString() . '">
                                <i class="fas fa-arrow-left"></i>
                            </a>',
                '#prefix' => '<div class="back-button>',
                '#suffix' => '</div>',
            ],
            'title' => [
                '#markup' => '<h2 class="mb-0">' . $form['#title'] . '</h2>',
            ],
        ];

        $this->buildCooperativeForm($form, $form_state, NULL);

        $form['actions'] = [
            '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
            '#button_type' => 'primary',
            "#attributes" => ['class' => ['btn', 'btn-coop-save'] ],
        ];

        $form['coop_id'] = [
            '#type' => 'hidden',
            '#value' => NULL,
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state) {
        $values = $form_state->getValues();

        try {
            $node = Node::create([
                'type' => 'cooperative',
                'title' => $values['coop_name'],
                'field_coop_name' => $values['coop_name'],
                'field_coop_code' => $values['coop_code'],
                'field_cic_provider_code' => $values['cic_provider_code'],
                'field_ho_address' => $values['ho_address'],
                'field_no_of_employees' => $values['no_of_employees'],
                'field_contact_person' => $values['contact_person'],
                'field_coop_contact_number' => $values['coop_contact_number'],
                'field_email' => $values['email'],
                'field_coop_status' => true,
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

        $form_state->setRedirect('cooperative.list');
        return;
    }
}