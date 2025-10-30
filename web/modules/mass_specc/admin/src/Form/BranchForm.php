<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\ReplaceCommand;
use Drupal\admin\Plugin\Validation\Constraint\AlphaNumericConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\EmailConstraintValidator;
use Drupal\admin\Plugin\Validation\Constraint\PhMobileNumberConstraintValidator;

class BranchForm extends FormBase
{

  public function getFormId()
  {
    return 'branch_add_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $branch_id = NULL, $branch_key = NULL)
  {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'common/char-count';
    $form['#attached']['library'][] = 'admin/branch_save';
    $form['#attached']['library'][] = 'common/contact-number';

    $form['#prefix'] = '<div id="branch-form-wrapper">';
    $form['#suffix'] = '</div>';

    $branch_data = [];
    $is_edit = FALSE;

    $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
    $staged_branches = $tempstore->get($id) ?? [];

    if ($branch_key && isset($staged_branches[$branch_key])) {
      $branch_data = $staged_branches[$branch_key];
      $is_edit = TRUE;
    } elseif ($branch_id || is_numeric($branch_key)) {
      $db_id = $branch_id ?: $branch_key;
      $existing_branch = Node::load($db_id);
      if ($existing_branch) {
        $branch_data = [
          'branch_id' => $existing_branch->id(),
          'branch_code' => $existing_branch->get('field_branch_code')->value,
          'branch_name' => $existing_branch->get('field_branch_name')->value,
          'branch_address' => $existing_branch->get('field_branch_address')->value,
          'contact_person' => $existing_branch->get('field_branch_contact_person')->value,
          'contact_number' => $existing_branch->get('field_branch_contact_number')->value,
          'email' => $existing_branch->get('field_branch_email')->value,
          'no_of_employees' => $existing_branch->get('field_branch_no_of_employees')->value,
          'branch_manager' => $existing_branch->get('field_branch_manager')->value,
          'branch_manager_contact_no' => $existing_branch->get('field_branch_manager_contact_no')->value,
          'no_of_members' => $existing_branch->get('field_branch_number_of_members')->value,
        ];
        $is_edit = TRUE;
      }
    }

    $defaults = fn($key) => $branch_data[$key] ?? '';


    $form['branch_name'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch Name'),
      '#required' => TRUE,
      '#default_value' => $defaults('branch_name'),
      '#attributes' => ['class' => ['js-char-count'], 'data-maxlength' => 100],
      '#description' => ['#markup' => '<span class="char-counter">0/100</span>'],
      '#maxlength' => 100,
      '#element_validate' => [[AlphaNumericConstraintValidator::class, 'validate']],
    ];

    $form['branch_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch Code'),
      '#required' => TRUE,
      '#default_value' => $defaults('branch_code'),
      '#attributes' => ['class' => ['js-char-count'], 'data-maxlength' => 20],
      '#description' => ['#markup' => '<span class="char-counter">0/20</span>'],
      '#maxlength' => 20,
      '#element_validate' => [[AlphaNumericConstraintValidator::class, 'validate']],
    ];

    $form['branch_code'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch Code'),
      '#access' => $is_edit,
      '#default_value' => $defaults('branch_code'),
      '#attributes' => [
        'class' => ['js-char-count'],
        'data-maxlength' => 20,
        'readonly' => 'readonly',
      ],
      '#description' => ['#markup' => '<span class="char-counter">0/20</span>'],
      '#maxlength' => 20,
      '#element_validate' => [[AlphaNumericConstraintValidator::class, 'validate']],
    ];

    $form['no_of_employees'] = [
      '#type' => 'number',
      '#title' => $this->t('No. of Employees in Branch'),
      '#default_value' => $defaults('no_of_employees'),
      '#min' => 0,
      '#max' => 9999,
    ];

    $form['contact_person'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact Person'),
      '#default_value' => $defaults('contact_person'),
      '#attributes' => ['class' => ['js-char-count'], 'data-maxlength' => 100],
      '#description' => ['#markup' => '<span class="char-counter">0/100</span>'],
      '#maxlength' => 100,
      '#element_validate' => [[AlphaNumericConstraintValidator::class, 'validate']],
    ];

    $form['contact_number'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Contact Number'),
      '#default_value' => $defaults('contact_number'),
      '#attributes' => [
        'class' => ['js-numeric-only'],
        'data-maxlength' => 11,
        'maxlength' => 11,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]*',
      ],
      '#description' => $this->t('Format: 09XXXXXXXXX'),
      '#maxlength' => 11,
      '#element_validate' => [[PhMobileNumberConstraintValidator::class, "validate"]],
    ];

    $form['email'] = [
      '#type' => 'email',
      '#title' => $this->t('Email'),
      '#default_value' => $defaults('email'),
      '#element_validate' => [[EmailConstraintValidator::class, 'validate']],
    ];

    $form['branch_manager'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch Manager'),
      '#default_value' => $defaults('branch_manager'),
      '#attributes' => ['class' => ['js-char-count'], 'data-maxlength' => 100],
      '#description' => ['#markup' => '<span class="char-counter">0/100</span>'],
      '#maxlength' => 100,
      '#element_validate' => [[AlphaNumericConstraintValidator::class, 'validate']],
    ];

    $form['branch_manager_contact_no'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Branch Manager Contact No.'),
      '#default_value' => $defaults('branch_manager_contact_no'),
      '#attributes' => [
        'class' => ['js-numeric-only'],
        'data-maxlength' => 11,
        'maxlength' => 11,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]*',
      ],
      '#description' => $this->t('Format: 09XXXXXXXXX'),
      '#maxlength' => 11,
      '#element_validate' => [[PhMobileNumberConstraintValidator::class, "validate"]],
    ];

    $form['no_of_members'] = [
      '#type' => 'number',
      '#title' => $this->t('No. of Members'),
      '#default_value' => $defaults('no_of_members'),
      '#min' => 0,
    ];

    $form['coop_id'] = ['#type' => 'hidden', '#value' => $id];

    $form['branch_id'] = [
      '#type' => 'hidden',
      '#value' => $branch_data['branch_id'] ?? ($branch_key && is_numeric($branch_key) ? $branch_key : NULL),
    ];

    $form['branch_key'] = [
      '#type' => 'hidden',
      '#value' => $branch_key ?? $branch_data['uuid'] ?? $branch_data['branch_id'] ?? NULL,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $is_edit ? $this->t('Save Changes') : $this->t('Create Branch'),
      '#button_type' => 'primary',
      '#ajax' => [
        'callback' => '::ajaxSubmit',
        'wrapper' => 'branches-table-wrapper',
        'effect' => 'fade',
      ],
      '#attributes' => ['class' => ['btn', 'branch-submit-btn']],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $coop_id = $form_state->getValue('coop_id');
    $branch_id = $form_state->getValue('branch_id');
    $branch_key = $form_state->getValue('branch_key');
    $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');

    $branches = $tempstore->get($coop_id) ?? [];

    $branch_data = [
      'branch_id' => $branch_id ?: NULL,
      'branch_name' => $form_state->getValue('branch_name'),
      'branch_address' => $form_state->getValue('branch_address'),
      'contact_person' => $form_state->getValue('contact_person'),
      'contact_number' => $form_state->getValue('contact_number'),
      'email' => $form_state->getValue('email'),
      'branch_manager' => $form_state->getValue('branch_manager'),
      'branch_manager_contact_no' => $form_state->getValue('branch_manager_contact_no'),
      'no_of_members' => $form_state->getValue('no_of_members'),
      'no_of_employees' => $form_state->getValue('no_of_employees'),
      'is_staged' => TRUE,
    ];

    $uuid_service = \Drupal::service('uuid');
    if (empty($branch_key)) {
      $branch_key = $uuid_service->generate();
    }

    $branch_data['uuid'] = $branch_key;

    $existing_key = NULL;
    foreach ($branches as $key => $existing_branch) {
      if (
        (!empty($existing_branch['uuid']) && $existing_branch['uuid'] === $branch_key) ||
        (!empty($branch_id) && !empty($existing_branch['branch_id']) && $existing_branch['branch_id'] == $branch_id)
      ) {
        $existing_key = $key;
        break;
      }
    }

    if ($existing_key !== NULL) {
      $branches[$existing_key] = array_merge($branches[$existing_key], $branch_data);
    } else {
      $branches[$branch_key] = $branch_data;
    }

    $tempstore->set($coop_id, $branches);
    $form_state->setRebuild(TRUE);
  }


  public function ajaxSubmit(array &$form, FormStateInterface $form_state)
  {
    $response = new AjaxResponse();

    if ($form_state->hasAnyErrors()) {
      $response->addCommand(new ReplaceCommand('#branch-form-wrapper', $form));
      return $response;
    }

    $coop_id = $form_state->getValue('coop_id');
    $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
    $staged = $tempstore->get($coop_id) ?? [];

    $existing_nids = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition('field_branch_coop', $coop_id)
      ->accessCheck(FALSE)
      ->execute();

    $existing_map = [];
    if (!empty($existing_nids)) {
      $nodes = Node::loadMultiple($existing_nids);
      foreach ($nodes as $node) {
        $existing_map[$node->id()] = [
          'branch_id' => $node->id(),
          'branch_code' => $node->get('field_branch_code')->value,
          'branch_name' => $node->get('field_branch_name')->value,
          'email' => $node->get('field_branch_email')->value,
          'contact_person' => $node->get('field_branch_contact_person')->value,
          'is_staged' => FALSE,
        ];
      }
    }

    foreach ($staged as $key => $branch) {
      $is_existing = !empty($branch['branch_id']) && isset($existing_map[$branch['branch_id']]);
      $target_key = $is_existing ? $branch['branch_id'] : $key;
      $branch['is_staged'] = TRUE;
      $existing_map[$target_key] = $branch;
    }

    $all_branches = array_values($existing_map);

    $table_render = \Drupal\admin\Component\CoopBranchesTable::render($coop_id, $all_branches);
    $table_render['#cache'] = ['max-age' => 0];
    $table_render['#attached']['drupalSettings']['refreshId'] = uniqid();

    $wrapper = [
      '#type' => 'container',
      '#attributes' => ['id' => 'branches-table-wrapper'],
      'table' => $table_render,
    ];

    $response->addCommand(new ReplaceCommand('#branches-table-wrapper', $wrapper));

    $total_members = 0;
    foreach ($all_branches as $branch) {
      $total_members += intval($branch['no_of_members'] ?? 0);
    }

    $response->addCommand(new InvokeCommand('#coop-no-of-members', 'val', [$total_members]));
    $response->addCommand(new InvokeCommand('#coop-no-of-members', 'prop', ['value', $total_members]));

    $response->addCommand(new MessageCommand($this->t('Branch staged successfully.'), NULL, ['type' => 'status']));
    $response->addCommand(new InvokeCommand('#save-branches-btn', 'prop', ['disabled', false]));
    $response->addCommand(new CloseDialogCommand());


    return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state)
  {
    $branch_code = $form_state->getValue('branch_code');
    $branch_name = $form_state->getValue('branch_name');
    $coop_id = $form_state->getValue('coop_id');
    $branch_id = $form_state->getValue('branch_id');
    $branch_key = $form_state->getValue('branch_key');

    $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
    $staged_branches = $tempstore->get($coop_id) ?? [];

    foreach ($staged_branches as $key => $branch) {
      if (
        ($branch_id && isset($branch['branch_id']) && $branch['branch_id'] == $branch_id) ||
        ($branch_key && isset($branch['uuid']) && $branch['uuid'] == $branch_key)
      ) {
        continue;
      }

      if (!empty($branch['branch_code']) && $branch['branch_code'] === $branch_code) {
        $form_state->setErrorByName('branch_code', $this->t('The Branch Code %code is already staged.', ['%code' => $branch_code]));
        return;
      }

      if (!empty($branch['branch_name']) && strcasecmp($branch['branch_name'], $branch_name) === 0) {
        $form_state->setErrorByName('branch_name', $this->t('The Branch Name %name is already staged.', ['%name' => $branch_name]));
        return;
      }
    }

    $query_code = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition('field_branch_code', $branch_code)
      ->accessCheck(FALSE);

    if ($branch_id) {
      $query_code->condition('nid', $branch_id, '<>');
    }

    $existing = $query_code->execute();

    if (!$branch_id && $branch_key && isset($staged_branches[$branch_key])) {
      return;
    }

    if (!empty($existing)) {
      $form_state->setErrorByName('branch_code', $this->t('The Branch Code %code is already in use.', ['%code' => $branch_code]));
    }
  }
}
