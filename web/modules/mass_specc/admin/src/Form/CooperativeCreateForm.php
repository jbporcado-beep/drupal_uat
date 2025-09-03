<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

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
        $form['columns'] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#attributes' => ['class' => ['mass-specc-form-columns']],
        ];

        $form['columns']['column1'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['mass-specc-form-column1']],
        ];
        $form['columns']['column1']['cooperative_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Cooperative Code'),
            '#required' => TRUE,
        ];
        $form['columns']['column1']['cic_provider_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CIC Provider Code'),
            '#required' => TRUE,
        ];
        $form['columns']['column1']['cbs_branch_code'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CBS Branch Code'),
            '#required' => TRUE,
        ];

        $form['columns']['column2'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['mass-specc-form-column2']],
        ];
        $form['columns']['column2']['name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Name of Cooperative'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['head_office_address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Head Office Address'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['no_of_employees'] = [
            '#type' => 'number',
            '#title' => $this->t('No. of Employees in Head Office'),
            '#min' => 0,
            '#required' => TRUE,
        ];
        $form['columns']['column2']['contact_person'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Person'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Number'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['cda_registration_date'] = [
            '#type' => 'datetime',
            '#title' => $this->t('CDA Registration Date'),
            '#required' => TRUE,
            '#date_date_element' => 'date',
            '#date_time_element' => 'none',
        ];
        $form['columns']['column2']['cda_firm_size'] = [
            '#type' => 'textfield',
            '#title' => $this->t('CDA Firm Size'),
            '#required' => TRUE,
        ];
        $form['columns']['column2']['assigned_report_templates'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Report Templates'),
            '#options' => [
                'template_1' => $this->t('Template 1'),
                'template_2' => $this->t('Template 2'),
                'template_3' => $this->t('Template 3'),
            ],
            '#required' => TRUE,
        ];

        // Submit button.
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

        $node = \Drupal\node\Entity\Node::create([
            'type' => 'cooperative',
            'title' => $values['columns']['column2']['name'],
            'field_coop_code' => $values['columns']['column1']['cooperative_code'],
            'field_cic_provider_code' => $values['columns']['column1']['cic_provider_code'],
            'field_cbs_branch_code' => $values['columns']['column1']['cbs_branch_code'],
            'field_head_office_address' => $values['columns']['column2']['head_office_address'],
            'field_number_of_employees' => $values['columns']['column2']['no_of_employees'],
            'field_contact_person' => $values['columns']['column2']['contact_person'],
            'field_contact_number' => $values['columns']['column2']['contact_number'],
            'field_email' => $values['columns']['column2']['email'],
            'field_cda_registration_date' => $values['columns']['column2']['cda_registration_date']->format('Y-m-d'),
            'field_cda_firm_size' => $values['columns']['column2']['cda_firm_size'],
            'field_assigned_report_templates' => $values['columns']['column2']['assigned_report_templates'],
        ]);
        $node->save();

        \Drupal::messenger()->addMessage($this->t('Cooperative created successfully.'));
    }

}