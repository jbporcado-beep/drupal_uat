<?php

namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Routing\RouteMatchInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class ExpiredPasswordForm extends BaseChangePasswordForm
{

    public function getFormId()
    {
        return 'expired_password_form';
    }

    protected $account;

    public function __construct(RouteMatchInterface $route_match)
    {
        $uid = $route_match->getParameter('uid');
        $this->account = User::load($uid);
    }

    public static function create(ContainerInterface $container)
    {
        return new static($container->get('current_route_match'));
    }

    public function buildForm(array $form, FormStateInterface $form_state, bool $resetPassword = FALSE)
    {
        $form['title'] = [
            '#markup' => '<h2 class="title">' . $this->t('Your password has expired') . '</h2>',
        ];

        $form['subtext'] = [
            '#markup' => '<p class="subtext">' . $this->t('Please update it to ensure your account remains secure.') . '</p>',
        ];

        $form['current_password'] = [
            '#type' => 'password',
            '#title' => $this->t('Current password'),
            '#required' => TRUE,
            '#placeholder' => $this->t('Enter current password'),
        ];

        $form = parent::buildForm($form, $form_state, TRUE);

        $form['actions']['waive'] = [
            '#type' => 'submit',
            '#value' => $this->t('Waive'),
            '#submit' => ['::waivePasswordChange'],
            '#button_type' => 'secondary',
            '#limit_validation_errors' => [],
            '#attributes' => ['class' => ['waive-password-btn']],
        ];

        return $form;
    }

    public function waivePasswordChange(array &$form, FormStateInterface $form_state)
    {
        if (!$this->account) {
            \Drupal::logger('password_reset')->error('Cannot waive password, user not loaded.');
            return;
        }

        $this->account->set('field_password_expiration', 0);
        $this->account->save();

        \Drupal::logger('password_reset')->notice('Password change waived for user ID: @uid', ['@uid' => $this->account->id()]);
        $this->messenger()->addStatus($this->t('You have waived the password change.'));
        $form_state->setRedirect('<front>');
    }


    public function validateForm(array &$form, FormStateInterface $form_state)
    {

        $current_password = $form_state->getValue('current_password');
        if (!\Drupal::service('user.auth')->authenticate($this->account->getAccountName(), $current_password)) {
            $form_state->setErrorByName('current_password', $this->t('Your current password does not match our records.'));
        }

        $this->validateNewPassword($form_state, $this->account);
    }


    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        if (!$this->account) {
            return;
        }

        $new_password = $form_state->getValue('new_password');
        $this->savePassword($this->account, $new_password);

        $this->account->set('field_password_expiration', 0);
        $this->account->save();

        $this->messenger()->addStatus($this->t('Your password has been changed successfully.'));
        $form_state->setRedirect('<front>');
    }
}
