<?php
namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
class OTPSenderForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'mass_specc_otp_sender_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#prefix'] = '<div class="password-reset-form">';
        $form['#suffix'] = '</div>';

        $form['title'] = [
            '#markup' => '<h2 class="title">' . $this->t('Reset Password') . '</h2>',
        ];
        $form['subtext'] = [
            '#markup' => '<p class="subtext">' . $this->t('Enter the email address associated with your account to receive a one-time password (OTP).') . '</p>',
        ];

        $form['email'] = [
            '#type' => 'email',
            '#placeholder' => $this->t('Enter your email address'),
            '#attributes' => ['class' => ['with-icon']],
            '#prefix' => '<div class="reset-pwd-email"><i class="fas fa-envelope"></i>',
            '#suffix' => '</div>',
        ];

        $form['actions']['#type'] = 'actions';
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Send OTP'),
            '#attributes' => ['class' => ['btn', 'btn-primary', 'otp-btn-submit']],
        ];

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $email = $form_state->getValue('email');
        if (!\Drupal::service('email.validator')->isValid($email) || !$user = user_load_by_mail($email) || $email === NULL) {
            $form_state->setErrorByName('email', $this->t('Please enter a valid email address.'));
        }
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $session = \Drupal::request()->getSession();
        $email = $form_state->getValue('email');
        $user = user_load_by_mail($email);

        if ($user) {
            $uid = $user->id();

            $code = random_int(100000, 999999);

            $session->set('otp_code:' . $uid, $code);

            $transaction_key = \Drupal::service('password_generator')->generate(32);
            $session->set('otp_validation_key:' . $transaction_key, $uid);

            $mailManager = \Drupal::service('plugin.manager.mail');
            $params = [
                'account' => $user,
                'message' => [
                    'subject' => 'Your verification code',
                    'body' => "Your one-time code is: $code",
                ],
            ];
            $langcode = $user->getPreferredLangcode();

            $result = $mailManager->mail('tfa_email_otp', 'otp', $email, $langcode, $params);

            if (!empty($result['result']) && $result['result'] === true) {
                $this->messenger()->addStatus($this->t(
                    'A verification code has been sent to @email.',
                    ['@email' => $email]
                ));

                $form_state->setTemporaryValue('user_id', $uid);
                $form_state->setRedirect('forgotpassword.otp_validator_form', [
                    'key' => $transaction_key,
                ]);
            } else {
                $this->messenger()->addError($this->t(
                    'Could not send the verification code. Please try again.'
                ));
            }
        }
    }
}
