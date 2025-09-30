<?php
namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;

class ResetPasswordForm extends BaseChangePasswordForm
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'password_reset_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $user = NULL)
    {
        $form['#attributes']['novalidate'] = 'novalidate';

        $form['title'] = [
            '#markup' => '<h2 class="title">' . $this->t('Reset Password') . '</h2>',
        ];
        $form['subtext'] = [
            '#markup' => '<p class="subtext">' . $this->t('Create a new password for your account.') . '</p>',
        ];

        $form['uid'] = [
            '#type' => 'hidden',
            '#value' => $user,
        ];

        $form = parent::buildForm($form, $form_state, TRUE);

        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/login-page';

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $uid = $form_state->getValue('uid');
        $session = \Drupal::request()->getSession();

        if (!$uid || (!$session->get('otp_verified:' . $uid) && !$session->get('one_time_login:' . $uid))) {
            $this->messenger()->addError($this->t('Unauthorized access.'));
            $form_state->setRedirect('user.login');
            return;
        }

        $account = User::load($uid);

        $this->validateNewPassword($form_state, $account);
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->hasAnyErrors()) {
            return;
        }
        $uid = $form_state->getValue('uid');
        $session = \Drupal::request()->getSession();

        if (!$uid) {
            $this->messenger()->addError($this->t('Unauthorized access.'));
            $form_state->setRedirect('user.login');
            return;
        }

        $account = User::load($uid);

        $new_password = $form_state->getValue('new_password');

        if ($account) {
            $this->savePassword($account, $new_password);
        } else {
            $this->messenger()->addError($this->t('User account not found.'));
            $form_state->setRedirect('user.login');
            return;
        }

        $session->remove('otp_verified:' . $uid);
        $session->remove('one_time_login:' . $uid);

        $this->messenger()->addStatus($this->t('Your password has been changed successfully. Please try to log in with your new password.'));
        $form_state->setRedirect('user.login');
    }

}
