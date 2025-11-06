<?php

namespace Drupal\cooperative\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\HtmlCommand;
use Drupal\cooperative\Utility\DomainLists;
/**
 * Provides a Member Credit form.
 */
class MemberCreditForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'member_credit_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#prefix'] = '<div id="member-credit-form-wrapper" class="member-credit-wrapper">';
        $form['#suffix'] = '</div>';
        $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

        $form['actions_top_wrapper'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['report-builder-actions-top'],
                'style' => 'display:flex; justify-content:flex-end; margin-bottom:10px;',
            ],
        ];

        $form['actions_top_wrapper']['actions_top'] = [
            '#type' => 'actions',
        ];

        $form['actions_top_wrapper']['actions_top']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Download'),
            '#button_type' => 'primary',
            '#ajax' => [
                'callback' => '::submitAjaxCallback',
                'wrapper' => 'member-credit-form-wrapper',
                'effect' => 'fade',
            ],
        ];


        $form['individual_details'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-individual-details"><div class="group-label">Individual Details</div>',
            '#suffix' => '</div>',
        ];

        $form['individual_details']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:grid; grid-template-columns: 1fr 1fr; gap:1rem;',
            ],
        ];

        $form['individual_details']['container']['first_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('First Name'),
            '#required' => TRUE,
        ];

        $form['individual_details']['container']['last_name'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Last Name'),
            '#required' => TRUE,
        ];

        $form['individual_details']['container']['dob'] = [
            '#type' => 'date',
            '#title' => $this->t('Date of Birth'),
        ];

        $form['individual_details']['container']['gender'] = [
            '#type' => 'select',
            '#title' => $this->t('Gender'),
            '#options' => [
                '' => $this->t('- Select -'),
                'M' => $this->t('Male'),
                'F' => $this->t('Female'),
            ],
        ];

        $id_type_options = ['' => $this->t('- Select ID Type -')];
        foreach (DomainLists::IDENTIFICATION_TYPE_DOMAIN as $key => $label) {
            $id_type_options["ident_$key"] = $this->t('Identification - @label', ['@label' => $label]);
        }
        foreach (DomainLists::ID_TYPE_DOMAIN as $key => $label) {
            $id_type_options["id_$key"] = $this->t('ID - @label', ['@label' => $label]);
        }

        $form['identification'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-identification"><div class="group-label">Identification Code</div>',
            '#suffix' => '</div>',
            '#tree' => TRUE,
        ];

        $form['identification']['id1'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:flex; flex-wrap:wrap; gap:1rem; width:100%;',
            ],
        ];
        $form['identification']['id1']['id_type'] = [
            '#type' => 'select',
            '#title' => $this->t('ID Type'),
            '#options' => $id_type_options,
            '#attributes' => [
                'style' => 'flex:1 1 300px;',
            ],
        ];
        $form['identification']['id1']['id_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID Number'),
            '#attributes' => [
                'style' => 'flex:1 1 300px;',
            ],
            '#states' => [
                'disabled' => [
                    ':input[name="identification[id1][id_type]"]' => ['value' => ''],
                ],
            ],
        ];

        $form['identification']['id2'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:flex; flex-wrap:wrap; gap:1rem; width:100%;',
            ],
        ];
        $form['identification']['id2']['id_type'] = [
            '#type' => 'select',
            '#title' => $this->t('ID Type'),
            '#options' => $id_type_options,
            '#attributes' => [
                'style' => 'flex:1 1 300px;',
            ],
        ];
        $form['identification']['id2']['id_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('ID Number'),
            '#attributes' => [
                'style' => 'flex:1 1 300px;',
            ],
            '#states' => [
                'disabled' => [
                    ':input[name="identification[id2][id_type]"]' => ['value' => ''],
                ],
            ],
        ];

        $form['address_data'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-address"><div class="group-label">Address Data</div>',
            '#suffix' => '</div>',
        ];

        $form['address_data']['address'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Address'),
        ];

        $form['contact_data'] = [
            '#type' => 'container',
            '#prefix' => '<div class="form-group group-contact"><div class="group-label">Contact Data</div>',
            '#suffix' => '</div>',
        ];

        $form['contact_data']['container'] = [
            '#type' => 'container',
            '#attributes' => [
                'style' => 'display:flex; flex-wrap:wrap; gap:1rem; width:100%;',
            ],
        ];

        $contact_type_options = ['' => $this->t('- Select Contact Type -')] +
            array_map(fn($label) => $this->t($label), DomainLists::CONTACT_TYPE_DOMAIN);

        $form['contact_data']['container']['contact_type'] = [
            '#type' => 'select',
            '#title' => $this->t('Contact Type'),
            '#options' => $contact_type_options,
            '#attributes' => [
                'id' => 'contact-type',
                'style' => 'flex:1 1 300px;',
            ],
        ];

        $form['contact_data']['container']['contact_value'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Value'),
            '#attributes' => [
                'id' => 'contact-value',
                'style' => 'flex:1 1 300px;',
            ],
            '#states' => [
                'disabled' => [
                    ':input[name="contact_type"]' => ['value' => ''],
                ],
            ],

        ];


        $form['status_messages'] = [
            '#type' => 'container',
            '#attributes' => ['id' => 'form-status-messages'],
        ];

        return $form;
    }

    public function ajaxEnableIdNumber(array &$form, FormStateInterface $form_state)
    {
        $trigger = $form_state->getTriggeringElement()['#parents'];
        $index = $trigger[2] ?? 0;

        $selected_type = $form_state->getValue(['identification', 'ids', $index, 'id_type']);
        if (!empty($selected_type)) {
            $form['identification']['ids'][$index]['id_number']['#disabled'] = FALSE;
        } else {
            $form['identification']['ids'][$index]['id_number']['#disabled'] = TRUE;
        }

        return $form['identification']['ids'][$index]['id_number'];
    }

    /**
     * {@inheritdoc}
     */
    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $dob_input = $form_state->getValue('dob');
        $dob_formatted = '';

        if (!empty($dob_input)) {
            [$year, $month, $day] = explode('-', $dob_input);
            $dob_formatted = sprintf('%02d%02d%04d', $day, $month, $year);
        }

        $id_entries = $form_state->getValue('identification') ?? [];

        $ids = [];
        foreach (['id1', 'id2'] as $field_key) {
            $entry = $id_entries[$field_key] ?? [];
            $raw_type = $entry['id_type'] ?? '';
            $id_number = trim($entry['id_number'] ?? '');

            if (empty($raw_type) || empty($id_number)) {
                continue;
            }

            if (str_starts_with($raw_type, 'ident_')) {
                $group = 'identification';
                $key = substr($raw_type, strlen('ident_'));
            } elseif (str_starts_with($raw_type, 'id_')) {
                $group = 'id';
                $key = substr($raw_type, strlen('id_'));
            } else {
                continue;
            }

            $ids[] = [
                'group' => $group,
                'id_type' => $key,
                'id_number' => $id_number,
            ];
        }

        $data = [
            'first_name' => $form_state->getValue('first_name'),
            'last_name' => $form_state->getValue('last_name'),
            'dob' => $dob_formatted,
            'gender' => $form_state->getValue('gender'),
            'ids' => $ids,
            'address' => $form_state->getValue('address'),
            'contact_type' => $form_state->getValue('contact_type'),
            'contact_value' => $form_state->getValue('contact_value'),
        ];

        $service = \Drupal::service('cooperative.member_credit_service');
        $matching_nids = $service->getMatchingIndividual($data);

        $matching_count = is_array($matching_nids) ? count($matching_nids) : 0;
        $form_state->set('matching_count', $matching_count);

        if ($matching_count === 1) {
            $form_state->set('matched_nid', reset($matching_nids));
        }

        if ($matching_count === 0) {
            $form_state->setErrorByName('first_name', $this->t('No matching member found for the provided details. Please adjust the fields and try again.'));
        }

        if ($matching_count > 1) {
            $form_state->setErrorByName('first_name', $this->t('Multiple matching members found. Please refine your search criteria.'));
        }
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $dob_input = $form_state->getValue(['dob']);
        $dob_formatted = '';
        if (!empty($dob_input)) {
            $date = \DateTime::createFromFormat('Y-m-d', $dob_input);
            if ($date) {
                $dob_formatted = $date->format('dmY');
            }
        }

        $id_entries = $form_state->getValue(['identification']) ?? [];

        $ids = [];
        foreach (['id1', 'id2'] as $field_key) {
            $entry = $id_entries[$field_key] ?? [];
            $raw_type = $entry['id_type'] ?? '';
            $id_number = trim($entry['id_number'] ?? '');

            if (empty($raw_type) || empty($id_number)) {
                continue;
            }

            if (str_starts_with($raw_type, 'ident_')) {
                $group = 'identification';
                $key = substr($raw_type, strlen('ident_'));
            } elseif (str_starts_with($raw_type, 'id_')) {
                $group = 'id';
                $key = substr($raw_type, strlen('id_'));
            } else {
                continue;
            }

            $ids[] = [
                'group' => $group,
                'id_type' => $key,
                'id_number' => $id_number,
            ];
        }


        $data = [
            'first_name' => $form_state->getValue(['first_name']),
            'last_name' => $form_state->getValue(['last_name']),
            'dob' => $dob_formatted,
            'gender' => $form_state->getValue(['gender']),
            'ids' => $ids,
            'address' => $form_state->getValue(['address']),
            'contact_type' => $form_state->getValue(['contact_type']),
            'contact_value' => $form_state->getValue(['contact_value']),

        ];

        $service = \Drupal::service('cooperative.member_credit_service');
        $matching_nids = $service->getMatchingIndividual($data);

        $matching_count = count($matching_nids);

        $form_state->set('matching_count', $matching_count);

        if ($matching_count === 1) {
            $nid = reset($matching_nids);
            $form_state->set('matched_nid', $nid);
        }

        $form_state->set('matching_count', count($matching_nids));
        $form_state->setRebuild(TRUE);
    }

    public function submitAjaxCallback(array &$form, FormStateInterface $form_state)
    {
        $response = new AjaxResponse();
        if ($form_state->hasAnyErrors()) {
            $messenger = \Drupal::messenger();
            $message_list = $messenger->all();

            $render_array = [
                '#theme' => 'status_messages',
                '#message_list' => $message_list,
            ];

            $messenger->deleteAll();

            $response->addCommand(new HtmlCommand('#form-status-messages', \Drupal::service('renderer')->renderRoot($render_array)));

            $response->addCommand(new HtmlCommand('#member-credit-form-wrapper', \Drupal::service('renderer')->renderRoot($form)));

            return $response;
        }

        $count = $form_state->get('matching_count');
        if ($count === 1) {
            $nid = $form_state->get('matched_nid');
            $member_node = \Drupal\node\Entity\Node::load($nid);

            if ($member_node) {
                $get_safe = function ($entity, string $field_name) {
                    if ($entity->hasField($field_name) && !$entity->get($field_name)->isEmpty()) {
                        return $entity->get($field_name)->value;
                    }
                    return '—';
                };

                $first_name = $get_safe($member_node, 'field_first_name');
                $last_name = $get_safe($member_node, 'field_last_name');
                $msp_code = $get_safe($member_node, 'field_msp_subject_code');
                $dob = $get_safe($member_node, 'field_date_of_birth');
                $gender = $get_safe($member_node, 'field_gender');

                $markup = '
                <div class="member-details-modal">
                    <table class="member-details-table">
                        <tbody>
                            <tr><th>Name</th><td>' . $first_name . ' ' . $last_name . '</td></tr>
                            <tr><th>MSP Member Code</th><td>' . $msp_code . '</td></tr>
                            <tr><th>Date of Birth</th><td>' . $dob . '</td></tr>
                            <tr><th>Gender</th><td>' . $gender . '</td></tr>
                        </tbody>
                    </table>
                    <div style="text-align:right; margin-top:1.2em;">
                        <a href="/reports/member-credit/download/' . $nid . '" class="btn btn-primary submit-modal-btn">
                            ' . $this->t('Download Report') . '
                        </a>
                    </div>
                </div>';

                $markup .= '
                <style>
                    .member-details-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-size: 14px;
                    }
                    .member-details-table th {
                        text-align: left;
                        padding: 6px 8px;
                        border-bottom: 1px solid #ccc;
                        background-color: #f8f8f8;
                        width: 40%;
                    }
                    .member-details-table td {
                        padding: 6px 8px;
                        border-bottom: 1px solid #e0e0e0;
                    }
                    .member-details-modal {
                        margin-top: 0.5rem;
                    }
                </style>
            ';

                $response->addCommand(new OpenModalDialogCommand(
                    $this->t('Matched Member Profile'),
                    $markup,
                    ['width' => '600']
                ));
            }

        } elseif ($count > 1) {
            \Drupal::messenger()->addStatus($this->t('Found @count matching members.', ['@count' => $count]));
        } else {
            \Drupal::messenger()->addWarning($this->t('No matching member found.'));
        }

        $messenger = \Drupal::messenger();
        $message_list = $messenger->all();

        $render_array = [
            '#theme' => 'status_messages',
            '#message_list' => $message_list,
        ];

        $messenger->deleteAll();

        $response->addCommand(new HtmlCommand('#form-status-messages', \Drupal::service('renderer')->renderRoot($render_array)));

        return $response;
    }

}
