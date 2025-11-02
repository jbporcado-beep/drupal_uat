<?php
namespace Drupal\password_reset\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Render\Markup;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Drupal\Core\Url;


class SetPasswordForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
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
    public function getFormId()
    {
        return 'set_password_form';
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $form['#attributes']['novalidate'] = 'novalidate';

        $route_match = \Drupal::routeMatch();
        $uid = $route_match->getParameter('uid');
        $timestamp = \Drupal::request()->query->get('timestamp');
        $hash = \Drupal::request()->query->get('hash');

        $account = User::load($uid);
        if (!$account) {
            $this->messenger()->addError($this->t('Invalid user.'));
            return [];
        }

        if (!$this->validateHash($account, $hash, $timestamp)) {
            $this->messenger()->addError($this->t('Invalid or expired link.'));
            $form_state->setRedirect('user.login');
            return;
        }

        if ($this->currentUser->isAuthenticated() && ((int) $this->currentUser->id() !== (int) $uid)) {
            $this->messenger()->addWarning($this->t(
                'You are currently signed in as @name. To set up the new account, please open the setup link in a browser where you are not signed in, or sign out first.',
                ['@name' => $this->currentUser->getDisplayName()]
            ));
            $url = Url::fromRoute('<front>')->setAbsolute()->toString();

            $response = new RedirectResponse($url);
            $response->send();

            exit;
        }

        $form['title'] = [
            '#markup' => '<h2 class="title">' . $this->t('Set Password') . '</h2>',
        ];
        $form['subtext'] = [
            '#markup' => '<p class="subtext">' . $this->t('Please set a new password for your account.') . '</p>',
        ];

        $form['uid'] = [
            '#type' => 'hidden',
            '#value' => $uid,
        ];

        $form['#attached']['drupalSettings']['password_reset'] = [
            'timestamp' => $timestamp,
            'hash' => $hash,
        ];

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
            '#value' => $this->t('Save'),
            '#attributes' => ['class' => ['btn', 'btn-secondary', 'submit-password']],
        ];

        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/login-page';

        return $form;
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $uid = $form_state->getValue('uid');

        $account = User::load($uid);


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
                'Password must contain at least 1 uppercase character.' => 'Password must contain a mix of uppercase and lowercase letters.',
                'Password must contain at least 1 lowercase character.' => 'Password must contain a mix of uppercase and lowercase letters.',
                'Password length must be at least 8 characters.' => 'Password must be 8-100 characters long.',
                'Password length must not exceed 16 characters.' => 'Password must be 8-100 characters long.',
                'Password must contain at least 1 numeric character.' => 'Password must contain at least one number.',
                'Password must contain at least 1 special character.' => 'Password must contain at least one symbol.',
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
                Markup::create('<span style="color:red;">' . $log_message . '</span>')
            );

        }
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        if ($form_state->hasAnyErrors()) {
            return;
        }
        $uid = $form_state->getValue('uid');

        $account = User::load($uid);

        $new_password = $form_state->getValue('new_password');

        if ($account) {
            $account->setPassword($new_password);
            $account->save();

            \Drupal::database()->insert('password_policy_history')
                ->fields([
                    'uid' => $account->id(),
                    'pass_hash' => $account->getPassword(),
                    'timestamp' => \Drupal::time()->getRequestTime(),
                ])
                ->execute();
        } else {
            $this->messenger()->addError($this->t('User account not found.'));
            $form_state->setRedirect('user.login');
            return;
        }

        $data = [
            'changed_fields' => [],
            'performed_by_name' => $account->getAccountName(),
        ];

        $email = $account->getEmail();
        $this->activityLogger->log('Successfully updated password', 'user', $account->id(), $data, NULL, $email);


        $this->messenger()->addStatus($this->t('Your password has been changed successfully. Please try to log in with your new password.'));
        $form_state->setRedirect('user.login');
    }

    public function validateHash(User $user, $hash, $timestamp)
    {
        if (!$user || !$hash || !$timestamp) {
            return FALSE;
        }

        $max_age = 24 * 60 * 60;
        if (\Drupal::time()->getRequestTime() - $timestamp > $max_age) {
            return FALSE;
        }

        $expected_hash = user_pass_rehash($user, $timestamp);

        return hash_equals($expected_hash, $hash);
    }

}
