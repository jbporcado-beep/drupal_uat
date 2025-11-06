<?php
namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
/**
 * OTP Validator Form for password reset.
 */
class OTPValidatorForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'mass_specc_otp_validator_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $transaction_key = NULL)
    {
        $route_match = \Drupal::routeMatch();
        $transaction_key = $route_match->getParameter('key');

        $form['title'] = [
            '#markup' => '<h2 class="title">' . $this->t('Enter One-Time Pin') . '</h2>',
        ];
        $form['subtext'] = [
            '#markup' => '<p class="subtext">' . $this->t('Please enter the OTP sent to your email address.') . '</p>',
        ];

        if (!$transaction_key) {
            $this->messenger()->addError($this->t('Invalid access to the verification form. Please request a new code.'));
            $form_state->setRedirect('forgotpassword.otp_sender_form');
            return $form;
        }

        $form['transaction_key'] = [
            '#type' => 'hidden',
            '#value' => $transaction_key,
        ];

        $form['otp'] = [
            '#type' => 'textfield',
            '#placeholder' => $this->t('Enter your OTP'),
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Submit OTP'),
            '#attributes' => ['class' => ['btn', 'btn-primary', 'otp-btn-submit']],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $otp = $form_state->getValue('otp');
        $transaction_key = $form_state->getValue('transaction_key');
        $session = \Drupal::request()->getSession();
        $attempts_key = 'otp_attempts:' . $transaction_key;
        $attempts = $session->get($attempts_key, 0);

        if ($attempts >= 2) {
            $this->messenger()->addError($this->t('Too many failed attempts. Please request a new code.'));
            $form_state->setRedirect('forgotpassword.otp_sender_form');
            return;
        }
        if (empty($otp) || !preg_match('/^\d{6}$/', $otp)) {
            $attempts++;
            $session->set($attempts_key, $attempts);
            $form_state->setErrorByName('otp', $this->t('The OTP you entered is incorrect. Please try again.'));
            return;
        }
        $uid = $session->get('otp_validation_key:' . $transaction_key);

        if (empty($uid)) {
            $form_state->setRedirect('forgotpassword.otp_sender_form');
            return;
        }
        $stored_otp = $session->get('otp_code:' . $uid);

        if (!$stored_otp || $otp != $stored_otp) {
            $attempts++;
            $session->set($attempts_key, $attempts);
            if ($attempts >= 2) {
                $this->messenger()->addError($this->t('Too many failed attempts. Please request a new code.'));
                $form_state->setRedirect('forgotpassword.otp_sender_form');
            } else {
                $form_state->setErrorByName('otp', $this->t('The OTP you entered is incorrect. Please try again.'));
                $form_state->setValue('otp', '');
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $transaction_key = $form_state->getValue('transaction_key');
        $session = \Drupal::request()->getSession();
        $attempts_key = 'otp_attempts:' . $transaction_key;
        $attempts = $session->get($attempts_key, 0);

        if ($attempts >= 2) {
            $this->messenger()->addError($this->t('Too many failed attempts. Please request a new code.'));
            $form_state->setRedirect('forgotpassword.otp_sender_form');
            return;
        }

        $uid = $session->get('otp_validation_key:' . $transaction_key);
        $stored_otp = $uid ? $session->get('otp_code:' . $uid) : NULL;

        if (!$stored_otp || $form_state->getValue('otp') != $stored_otp) {
            $attempts++;
            $session->set($attempts_key, $attempts);

            if ($attempts >= 2) {
                $form_state->setRedirect('forgotpassword.otp_sender_form');
            } else {
                $form_state->setRebuild();
            }
            return;
        }

        $session->remove('otp_code:' . $uid);
        $session->remove('otp_validation_key:' . $transaction_key);
        $session->remove($attempts_key);
        $session->set('otp_verified:' . $uid, TRUE);
        $this->messenger()->addStatus($this->t('Verification successful!'));
        $form_state->setRedirect('reset_password.form', ['user' => $uid]);
    }

}
