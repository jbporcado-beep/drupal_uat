<?php
namespace Drupal\admin\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class CooperativeCreateForm extends CooperativeBaseForm
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'mass_specc_cooperative_create_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, \Drupal\Core\Form\FormStateInterface $form_state)
    {

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
            "#attributes" => ['class' => ['btn', 'btn-coop-save']],
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
    public function submitForm(array &$form, \Drupal\Core\Form\FormStateInterface $form_state)
    {
        $keyAscii = $_ENV['FTPS_PW_ENCRYPT_KEY'];
        $key = Key::loadFromAsciiSafeString($keyAscii);

        $values = $form_state->getValues();

        try {
            $node = Node::create([
                'type' => 'cooperative',
                'title' => $values['coop_name'],
                'field_coop_name' => $values['coop_name'],
                'field_coop_code' => $values['coop_code'],
                'field_cic_provider_code' => $values['cic_provider_code'],
                'field_coop_acronym' => $values['coop_acronym'],
                'field_no_of_employees' => $values['no_of_employees'],
                'field_street_no_and_name' => $values['street_name'],
                'field_postal_code' => $values['postal_code'],
                'field_subdivision_purok' => $values['subdivision'],
                'field_barangay' => $values['barangay'],
                'field_city' => $values['city'],
                'field_province' => $values['province'],
                'field_country' => $values['country'],
                'field_contact_person' => $values['contact_person'],
                'field_coop_contact_number' => $values['coop_contact_number'],
                'field_email' => $values['email'],
                'field_general_manager' => $values['manager'],
                'field_general_manager_contact_no' => $values['manage_contact_number'],
                'field_cooperative_website_url' => $values['coop_website'],
                'field_cooperative_tin' => $values['coop_tin'],
                'field_coop_type' => $values['coop_type'],
                'field_number_of_branches' => $values['no_of_branches'],
                'field_number_of_members' => $values['no_of_members'],
                'field_coop_status' => TRUE,
                'field_cda_registration_date' => $values['cda_registration_date'],
                'field_cda_firm_size' => $values['cda_firm_size'],
                'field_assigned_report_templates' => array_map('intval', $values['assigned_report_templates'] ?? []),
                'field_ftps_username' => $values['ftps_username'],
                'field_ftps_password' => Crypto::encrypt($values['ftps_password'], $key),
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