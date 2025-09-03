<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;

class UserCreateForm extends FormBase {

    /**
     * {@inheritdoc}
     */
    public function getFormId() {
        return 'mass_specc_user_create_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state) {
        $role = $form_state->getValue('role', 'approver');

        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
        ];

        $form['fullname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Full Name'),
            '#required' => TRUE,
        ];

        $form['contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Number'),
            '#required' => TRUE,
        ];

        $form['role'] = [
            '#type' => 'select',
            '#title' => $this->t('Role'),
            '#options' => [
                'access' => $this->t('Access'),
                'approver' => $this->t('Approver'),
                'uploader' => $this->t('Uploader'),
            ],
            '#default_value' => $role,
            '#ajax' => [
                'callback' => '::updateCoopBranchFields',
                'event' => 'change',
                'wrapper' => 'coop-branch-fields-wrapper',
            ],
            '#required' => TRUE,
        ];

        $form['coop_branch_fields'] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#attributes' => ['id' => 'coop-branch-fields-wrapper'],
        ];

        $form['coop_branch_fields']['assigned_cooperative'] = [
            '#type' => 'entity_autocomplete',
            '#title' => $this->t('Assigned Cooperative'),
            '#target_type' => 'node',
            '#selection_settings' => [
                'target_bundles' => ['cooperative'],
            ],
            '#required' => TRUE,
        ];

        $form['coop_branch_fields']['assigned_branch'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Assigned Branch'),
            '#required' => TRUE,
        ];

        $form['actions'] = [
            '#type' => 'actions',
        ];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Create User'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state) {
        $email = $form_state->getValue('email');
        $fullname = $form_state->getValue('fullname');
        $contact = $form_state->getValue('contact_number');
        $role = $form_state->getValue('role');
        $assigned_coop = $form_state->getValue(['coop_branch_fields', 'assigned_cooperative']);

        $user = User::create([
            'name' => $email,
            'mail' => $email,
            'field_full_name' => $fullname,
            'field_contact_number' => $contact,
            'field_cooperative' => ['target_id' => $assigned_coop],
            'status' => 1,
        ]);

        $user->addRole($role);
        $user->save();

        $timestamp = \Drupal::time()->getRequestTime();
        $hash = user_pass_rehash($user, $timestamp);

        $set_password_link = Url::fromRoute('user.reset', [
        'uid' => $user->id(),
        'timestamp' => $timestamp,
        'hash' => $hash,
        ], ['absolute' => TRUE])->toString();

        $site_name = \Drupal::config('system.site')->get('name');
        $username = $user->getAccountName();

        $subject = t('Set your password for @site', ['@site' => $site_name]);

        $body = t('
            <p>Hello,</p>
            <p>An account has been created for you on <strong>@site</strong>.</p>
            <p>Your username: <strong>@username</strong></p>
            <p>
            <a href="@link" style="display:inline-block;padding:10px 20px;background-color:#0074bd;color:#fff;text-decoration:none;border-radius:4px;">
                Set your password
            </a>
            </p>
            <p>This link will expire after one use.</p>
        ', [
            '@site' => $site_name,
            '@username' => $username,
            '@link' => $set_password_link,
        ]);

        $params['subject'] = $subject;
        $params['body'][] = $body;

        $mailManager = \Drupal::service('plugin.manager.mail');
        $mailManager->mail('admin', 'custom_password_reset', $user->getEmail(), $user->getPreferredLangcode(), $params, NULL, TRUE);

        \Drupal::messenger()->addMessage($this->t('User account created. Email sent to @email.', ['@email' => $email]));
    }
}