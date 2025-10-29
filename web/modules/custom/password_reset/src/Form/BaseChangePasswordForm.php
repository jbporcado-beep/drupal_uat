<?php

namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
abstract class BaseChangePasswordForm extends FormBase
{
    protected $currentUser;
    protected $activityLogger;
    public function __construct(UserActivityLogger $activityLogger, AccountProxyInterface $currentUser)
    {
        $this->activityLogger = $activityLogger;
        $this->currentUser = $currentUser;
    }

    public static function create(ContainerInterface $container)
    {
        return new static(
            $container->get('admin.user_activity_logger'),
            $container->get('current_user')
        );
    }
    public function buildForm(array $form, FormStateInterface $form_state, bool $resetPassword = FALSE)
    {
        $form['#attributes']['novalidate'] = 'novalidate';

        $form['new_password'] = [
            '#type' => 'password',
            '#title' => $this->t('New password'),
            '#placeholder' => $this->t('Enter new password'),
            '#required' => TRUE,
        ];

        $form['confirm_password'] = [
            '#type' => 'password',
            '#title' => $this->t('Confirm Password'),
            '#placeholder' => $this->t('Re-enter password'),
            '#required' => TRUE,
        ];

        $form['password_requirements'] = [
            '#markup' => '<div class="password-requirements">'
                . '<strong>' . $this->t('Password must contain:') . '</strong>'
                . '<ul>'
                . '<li>' . $this->t('At least 8 characters') . '</li>'
                . '<li>' . $this->t('At least 1 uppercase and lowercase letter') . '</li>'
                . '<li>' . $this->t('At least 1 number') . '</li>'
                . '<li>' . $this->t('At least 1 special character') . '</li>'
                . '</ul>'
                . '</div>',
        ];

        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $resetPassword ? $this->t('Change Password') : $this->t('Save'),
            '#attributes' => ['class' => $resetPassword ? ['btn', 'btn-primary', 'submit-reset-password'] : ['btn', 'btn-secondary', 'submit-password']],
        ];

        return $form;
    }

    protected function validateNewPassword(FormStateInterface $form_state, User $account = NULL)
    {
        $new_password = $form_state->getValue('new_password') ?? '';
        $confirm_password = $form_state->getValue('confirm_password') ?? '';

        if ($new_password !== $confirm_password) {
            $form_state->setErrorByName('confirm_password', $this->t('Passwords do not match.'));
        }

        $validator = \Drupal::service('password_policy.validator');
        $roles = $account->getRoles();
        $report = $validator->validatePassword($new_password, $account, $roles);

        $log_message = 'Password validation passed or policy not applied.';
        $log_messages = [];
        if ($report->isInvalid()) {
            $error_object = $report->getErrors();
            $arguments = $error_object->getArguments();
            $raw_message = '';

            if (isset($arguments['@message'])) {
                $nested_markup_object = $arguments['@message'];
                $raw_message = (string) $nested_markup_object;
            } else {
                $raw_message = (string) $error_object;
            }

            $message_map = [
                'Password must contain at least 1 uppercase character.' => 'Your password must contain a mix of uppercase and lowercase letters.',
                'Password must contain at least 1 lowercase character.' => 'Your password must contain a mix of uppercase and lowercase letters.',
                'Password length must be at least 8 characters.' => 'Your password must be 8-100 characters long.',
                'Password length must not exceed 16 characters.' => 'Your password must be 8-100 characters long.',
                'Password must contain at least 1 numeric character.' => 'Your password must contain at least one number.',
                'Password must contain at least 1 special character.' => 'Your password must contain at least one symbol.',
                'The last 3 passwords cannot be reused. Choose a different password.' => 'You cannot reuse your last 3 passwords. Choose a different password.',
            ];

            foreach ($message_map as $raw => $friendly) {
                if (str_contains($raw_message, $raw)) {
                    $log_messages[] = $friendly;
                }
            }

            if (empty($log_messages)) {
                $log_messages[] = $raw_message;
            }

            $log_message = implode('<br>', array_unique($log_messages));

            $form_state->setErrorByName(
                'new_password',
                Markup::create('<span style="color:red;s">' . $log_message . '</span>')
            );

        }
    }

    protected function savePassword(User $account, $new_password)
    {
        $account->setPassword($new_password);
        $account->save();

        \Drupal::database()->insert('password_policy_history')
            ->fields([
                'uid' => $account->id(),
                'pass_hash' => $account->getPassword(),
                'timestamp' => \Drupal::time()->getRequestTime(),
            ])
            ->execute();

        $email = $account->getEmail();
        $this->activityLogger->log('Successfully updated password', 'user', $account->id(), [], NULL, $email);
    }
}
