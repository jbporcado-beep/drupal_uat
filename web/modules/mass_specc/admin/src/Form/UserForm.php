<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\user\Entity\User;
use Drupal\Core\Url;
use Drupal\admin\Plugin\Validation\Constraint\AlphaNumericConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\EmailConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\PhMobileNumberConstraintValidator;
use Drupal\node\Entity\Node;

use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
class UserForm extends FormBase
{

    /**
     * {@inheritdoc}
     */
    public function getFormId()
    {
        return 'mass_specc_user_form';
    }

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

    /**
     * {@inheritdoc}
     */
    public function buildForm(array $form, FormStateInterface $form_state, $user = NULL)
    {
        $form['#attached']['library'][] = 'common/char-count';
        $form['#attached']['library'][] = 'common/contact-number';

        $form['#title'] = $user ? $this->t('Edit User') : $this->t('Add User');

        $form['header'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['form-header'],
            ],
            'back' => [
                '#markup' => '<a href="' . Url::fromRoute('users.list')->toString() . '">
                                <i class="fas fa-arrow-left"></i>
                            </a>',
                '#prefix' => '<div class="back-button>',
                '#suffix' => '</div>',
            ],
            'title' => [
                '#markup' => '<h2 class="mb-0">' . $form['#title'] . '</h2>',
            ],
        ];

        $form['email'] = [
            '#type' => 'email',
            '#title' => $this->t('Email'),
            '#required' => TRUE,
            '#default_value' => $user ? $user->getEmail() : '',
            '#element_validate' => [
                [EmailConstraintValidator::class, 'validate'],
            ],
            '#disabled' => $user ? TRUE : FALSE,
        ];

        $form['fullname'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Full Name'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-char-count'],
                'data-maxlength' => 100,
            ],
            '#description' => ['#markup' => '<span class="char-counter">0/100</span>'],
            '#maxlength' => 100,
            '#default_value' => $user ? ($user->get('field_full_name')->value ?? '') : '',
            '#element_validate' => [
                [AlphaNumericConstraintValidator::class, 'validate'],
            ],
        ];

        $form['contact_number'] = [
            '#type' => 'textfield',
            '#title' => $this->t('Contact Number'),
            '#required' => TRUE,
            '#attributes' => [
                'class' => ['js-numeric-only'],
                'data-maxlength' => 11,
                'maxlength' => 11,
                'inputmode' => 'numeric',
                'pattern' => '[0-9]*',
            ],
            '#description' => $this->t('Format: 09XXXXXXXXX'),
            '#default_value' => $user ? ($user->get('field_contact_number')->value ?? '') : '',
            '#element_validate' => [
                [PhMobileNumberConstraintValidator::class, 'validate'],
            ],
        ];

        $roles = $user ? $user->getRoles() : [];
        $default_role = in_array('approver', $roles) ? 'approver' : (in_array('uploader', $roles) ? 'uploader' : 'access');
        $form['role'] = [
            '#type' => 'select',
            '#title' => $this->t('Role'),
            '#options' => [
                'access' => $this->t('Access'),
                'approver' => $this->t('Approver'),
                'uploader' => $this->t('Uploader'),
            ],
            '#required' => TRUE,
            '#default_value' => $default_role,
            '#ajax' => [
                'callback' => '::updateBranchField',
                'event' => 'change',
                'wrapper' => 'assigned-branch-wrapper',
                'progress' => ['type' => 'throbber', 'message' => NULL],
            ],
        ];

        $form['coop_branch_fields'] = [
            '#type' => 'container',
            '#tree' => TRUE,
            '#attributes' => ['id' => 'coop-branch-fields-wrapper'],
        ];

        $cooperative_options = [];
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'cooperative')
            ->condition('field_coop_status', 1)
            ->accessCheck(FALSE)
            ->execute();
        if ($nids) {
            $nodes = Node::loadMultiple($nids);
            foreach ($nodes as $node) {
                $cooperative_options[$node->id()] = $node->getTitle();
            }
        }

        $assigned_coop_id = $user ? $user->get('field_cooperative')->target_id : NULL;
        $selected_coop = $form_state->getValue(['coop_branch_fields', 'assigned_cooperative'], $assigned_coop_id);

        $form['coop_branch_fields']['assigned_cooperative'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Cooperative'),
            '#options' => $cooperative_options,
            '#required' => FALSE,
            '#empty_option' => $this->t('- Select a cooperative -'),
            '#default_value' => $selected_coop,
            '#ajax' => [
                'callback' => '::updateBranchField',
                'event' => 'change',
                'wrapper' => 'assigned-branch-wrapper',
                'progress' => ['type' => 'throbber', 'message' => NULL],
            ],
            '#chosen' => TRUE,
            '#disabled' => $user ? TRUE : FALSE,
        ];

        $branch_options = ['' => $this->t('- Select a branch -')];
        if ($selected_coop) {
            $branch_nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
                'type' => 'branch',
                'field_branch_coop' => $selected_coop,
            ]);
            foreach ($branch_nodes as $bn) {
                $branch_options[$bn->id()] = $bn->label();
            }
        }

        $assigned_branch_id = $user ? $user->get('field_branch')->target_id : NULL;
        $selected_branch = $form_state->getValue(['coop_branch_fields', 'assigned_branch'], $assigned_branch_id);

        $isApprover = $form_state->getValue('role', $default_role) === 'approver';

        $form['coop_branch_fields']['assigned_branch'] = [
            '#type' => 'select',
            '#title' => $this->t('Assigned Branch'),
            '#options' => $branch_options,
            '#default_value' => $isApprover ? '' : $selected_branch,
            '#required' => !$isApprover,
            '#empty_option' => $this->t('- Select a branch -'),
            '#prefix' => '<div id="assigned-branch-wrapper">',
            '#suffix' => '</div>',
            '#attributes' => $isApprover ? ['disabled' => 'disabled'] : [],
        ];

        $form['actions'] = ['#type' => 'actions'];
        $form['actions']['submit'] = [
            '#type' => 'submit',
            '#value' => $this->t('Save'),
        ];

        return $form;
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $email = $form_state->getValue('email');
        $fullname = $form_state->getValue('fullname');
        $contact = $form_state->getValue('contact_number');
        $role = $form_state->getValue('role');
        $assigned_coop = $form_state->getValue(['coop_branch_fields', 'assigned_cooperative']);
        $assigned_branch = $form_state->getValue(['coop_branch_fields', 'assigned_branch']);

        if ($role === 'approver') {
            $assigned_branch = NULL;
        }


        $user = $form_state->getBuildInfo()['args'][0] ?? NULL;

        try {
            if ($user) {
                $old_role = '';
                foreach ($user->getRoles() as $r) {
                    if ($r !== 'authenticated') {
                        $old_role = $r;
                    }
                }

                $old_branch = $user->get('field_branch')->entity ? $user->get('field_branch')->entity->getTitle() : NULL;

                $user->set('field_full_name', $fullname);
                $user->set('field_contact_number', $contact);
                $user->set('field_branch', ['target_id' => $assigned_branch]);

                foreach ($user->getRoles() as $r) {
                    if ($r !== 'authenticated') {
                        $user->removeRole($r);
                    }
                }

                $user->addRole($role);

                $data = [
                    'changed_fields' => [],
                    'performed_by_name' => $this->currentUser->getDisplayName(),
                ];

                $new_coop = $assigned_coop ? Node::load($assigned_coop)->getTitle() : NULL;
                $new_branch = $assigned_branch ? Node::load($assigned_branch)->getTitle() : NULL;

                if ($old_role !== $role) {
                    $action = 'Updated ' . $old_role . ' user ' . $email . ' to ' . $role;
                    $this->activityLogger->log(
                        $action,
                        'user',
                        $user->id(),
                        $data,
                        NULL,
                        $this->currentUser
                    );
                }

                $action = '';
                if (($new_coop && $new_branch) && $old_branch !== $new_branch) {
                    $action = 'Assigned ' . $email . ' to ' . $new_coop . ' - ' . $new_branch;
                }
                $this->activityLogger->log(
                    $action,
                    'user',
                    $user->id(),
                    $data,
                    NULL,
                    $this->currentUser
                );

                $user->save();
                \Drupal::messenger()->addMessage($this->t('User account updated.'));
            } else {

                $user = User::create([
                    'name' => $email,
                    'mail' => $email,
                    'field_full_name' => $fullname,
                    'field_contact_number' => $contact,
                    'field_cooperative' => ['target_id' => $assigned_coop],
                    'field_branch' => ['target_id' => $assigned_branch],
                    'status' => 1,
                ]);
                $user->addRole($role);
                $user->save();

                $timestamp = \Drupal::time()->getRequestTime();
                $hash = user_pass_rehash($user, $timestamp);

                $set_password_link = Url::fromRoute('set_password.form', [
                    'uid' => $user->id(),
                ], [
                    'absolute' => TRUE,
                    'query' => [
                        'timestamp' => $timestamp,
                        'hash' => $hash,
                    ],
                ])->toString();

                $params = [
                    'user' => $user,
                    'link' => $set_password_link,
                ];

                $mailManager = \Drupal::service('plugin.manager.mail');
                $mailManager->mail(
                    'admin',
                    'custom_password_reset',
                    $user->getEmail(),
                    $user->getPreferredLangcode(),
                    $params,
                    NULL,
                    TRUE
                );

                $coop_label = NULL;
                $branch_label = NULL;

                if (!empty($assigned_coop)) {
                    $coop_node = Node::load($assigned_coop);
                    $coop_label = $coop_node ? $coop_node->getTitle() : NULL;
                }

                if (!empty($assigned_branch)) {
                    $branch_node = Node::load($assigned_branch);
                    $branch_label = $branch_node ? $branch_node->getTitle() : NULL;
                }

                $suffix = '';
                if ($coop_label && $branch_label) {
                    $suffix = ' for ' . $coop_label . ' - ' . $branch_label;
                } elseif ($coop_label) {
                    $suffix = ' for ' . $coop_label;
                }

                $data = [
                    'changed_fields' => [],
                    'performed_by_name' => $this->currentUser->getDisplayName(),
                ];


                $action = 'Created new ' . $role . ' user ' . $email . $suffix;
                $this->activityLogger->log($action, 'node', NULL, $data, NULL, $this->currentUser);

                \Drupal::messenger()->addMessage($this->t('User account created. Email sent to @email.', ['@email' => $email]));
            }
        } catch (\Exception $e) {
            \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
        }

        $form_state->setRedirect('users.list');
    }

    public function updateBranchField(array &$form, FormStateInterface $form_state)
    {
        \Drupal::messenger()->deleteAll();

        $role = $form_state->getValue('role');
        $coop_id = $form_state->getValue(['coop_branch_fields', 'assigned_cooperative']);

        $options = ['' => $this->t('- Select a branch -')];
        if (!empty($coop_id)) {
            $nodes = \Drupal::entityTypeManager()->getStorage('node')->loadByProperties([
                'type' => 'branch',
                'field_branch_coop' => $coop_id,
            ]);
            foreach ($nodes as $node) {
                $options[$node->id()] = $node->label();
            }
        }

        $form['coop_branch_fields']['assigned_branch']['#options'] = $options;

        if ($role !== 'approver' && !empty($coop_id)) {
            $form['coop_branch_fields']['assigned_branch']['#required'] = TRUE;
            unset($form['coop_branch_fields']['assigned_branch']['#attributes']['disabled']);
        } else {
            $form['coop_branch_fields']['assigned_branch']['#required'] = FALSE;
            $form['coop_branch_fields']['assigned_branch']['#default_value'] = '';
            $form['coop_branch_fields']['assigned_branch']['#value'] = '';
            $form['coop_branch_fields']['assigned_branch']['#attributes']['disabled'] = 'disabled';
        }

        return $form['coop_branch_fields']['assigned_branch'];
    }

    public function validateForm(array &$form, FormStateInterface $form_state)
    {
        $role = $form_state->getValue('role');
        $branch = $form_state->getValue(['coop_branch_fields', 'assigned_branch']);
        $email = trim($form_state->getValue('email'));

        if ($role !== 'approver' && empty($branch)) {
            $form_state->setErrorByName('coop_branch_fields][assigned_branch', $this->t('Branch is required for this role.'));
        }

        if (strlen($form_state->getValue('fullname')) < 3) {
            $form_state->setErrorByName('fullname', $this->t('Full name must be at least 3 characters long.'));
        }

        if ($email) {
            $user = $form_state->getBuildInfo()['args'][0] ?? NULL;
            $current_uid = $user ? $user->id() : NULL;

            $query = \Drupal::entityQuery('user')
                ->condition('mail', $email);

            if ($current_uid) {
                $query->condition('uid', $current_uid, '!=');
            }

            $existing = $query->accessCheck(FALSE)->execute();

            if (!empty($existing)) {
                $form_state->setErrorByName('email', $this->t('The email %email is already in use.', [
                    '%email' => $email,
                ]));
            }
        }
    }

}