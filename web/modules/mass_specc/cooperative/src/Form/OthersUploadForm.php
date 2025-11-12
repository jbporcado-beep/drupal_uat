<?php

namespace Drupal\cooperative\Form;

use Drupal\Core\Url;
use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\admin\Service\UserActivityLogger;

use Drupal\Component\Serialization\Json;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\OpenModalDialogCommand;
use Drupal\Core\Ajax\CloseModalDialogCommand;
use Drupal\Core\Ajax\RedirectCommand;


/**
 * Provides a simple upload form example with a submit handler.
 */

class OthersUploadForm extends FormBase
{
  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  protected $activityLogger;

  public function __construct(
    AccountProxyInterface $current_user,
    UserActivityLogger $activityLogger
  ) {
    $this->currentUser = $current_user;
    $this->activityLogger = $activityLogger;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('current_user'),
      $container->get('admin.user_activity_logger'),
    );
  }

  private function getCooperatives(): array
  {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'cooperative')
      ->condition('field_coop_status', 1)
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }
    return $options;
  }

  private function getBranches(): array
  {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }
    return $options;
  }

  private function getReports(): array
  {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report_template')
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $options[$node->id()] = $node->getTitle();
      }
    }
    return $options;
  }

  public function updateDropdownsFromCoop(array &$form, FormStateInterface $form_state)
  {
    return $form['layout']['dropdowns_wrapper'];
  }

  public function updateDropdownsFromBranch(array &$form, FormStateInterface $form_state)
  {
    return $form['layout']['dropdowns_wrapper'];
  }

  private function getBranchOptionsByCoop(int $coop_nid): array
  {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition('field_branch_coop', $coop_nid)
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $branch_nodes = $node_storage->loadMultiple($nids);
      foreach ($branch_nodes as $branch) {
        $options[$branch->id()] = $branch->get('field_branch_name')->value;
      }
    }
    return $options;
  }

  private function getCoopOptionsByBranch(int $branch_nid): array
  {
    $options = [];
    $branch = Node::load($branch_nid);
    if ($branch && $branch->hasField('field_branch_coop') && !$branch->get('field_branch_coop')->isEmpty()) {
      $coop = $branch->get('field_branch_coop')->entity;
      if ($coop) {
        $options[$coop->id()] = $coop->get('field_coop_name')->value;
      }
    }
    return $options;
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string
  {
    return 'cooperative_upload_form_others';
  }

  /** 
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array
  {
    $form['#attached']['library'][] = 'core/drupal.dialog';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/file-upload-styles';

    $form['layout'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-page-layout']],
    ];
    $form['layout']['info_text'] = [
      '#type' => 'markup',
      '#markup' => '<h1>' . $this->t('Others File Upload') . '</h1>',
    ];

    $uid = \Drupal::currentUser()->id();
    $user = $uid > 0 ? User::load($uid) : NULL;
    $is_uploader = $user->hasRole('uploader') && !$user->hasRole('administrator') && !$user->hasRole('mass_specc_admin');
    $is_admin = $user->hasRole('administrator') || $user->hasRole('mass_specc_admin');

    $coop_options = $this->getCooperatives();
    $branch_options = $this->getBranches();
    $report_options = $this->getReports();

    if ($is_uploader) {
      $coop_entity = $user->get('field_cooperative')->referencedEntities();
      $coop = reset($coop_entity);
      $coop_options = [];
      if ($coop) {
        $coop_options[$coop->id()] = $coop->get('field_coop_name')->value;
        $coop_reports = $coop->get('field_assigned_report_templates')->referencedEntities();
        if (!empty($coop_reports)) {
            foreach ($coop_reports as $report) {
              $report_options[$report->id()] = $report->getTitle();
            }
        }
        else {
            $report_options = [];
        }
      }

      $branch_entity = $user->get('field_branch')->referencedEntities();
      $branch = reset($branch_entity);
      $branch_options = [];
      if ($branch) {
        $branch_options[$branch->id()] = $branch->get('field_branch_name')->value;
      }
    } 
    else if ($is_admin) {
      $selected_coop = $form_state->getValue('coop_dropdown');
      $selected_branch = $form_state->getValue('branch_dropdown');
      $triggering_element = $form_state->getTriggeringElement();

      if ($selected_coop) {
        $branch_options = $this->getBranchOptionsByCoop($selected_coop);
      } else {
        $branch_options = $this->getBranches();
      }

      if ($selected_branch) {
        $coop_options = $this->getCoopOptionsByBranch($selected_branch);
      } else {
        $coop_options = $this->getCooperatives();
      }
    }


    $form['layout']['dropdowns_wrapper'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['dropdowns-wrapper'],
        'id' => 'dropdowns-wrapper',
      ],
    ];
    $form['layout']['dropdowns_wrapper']['coop_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Cooperative'),
      '#options' => $coop_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#required' => TRUE,
      '#disabled' => $is_uploader,
      '#ajax' => [
        'callback' => '::updateDropdownsFromCoop',
        'wrapper' => 'dropdowns-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#empty_option' => $this->t('- Select a cooperative -'),
    ];
    $form['layout']['dropdowns_wrapper']['branch_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Branch'),
      '#options' => $branch_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#required' => TRUE,
      '#disabled' => $is_uploader,
      '#ajax' => [
        'callback' => '::updateDropdownsFromBranch',
        'wrapper' => 'dropdowns-wrapper',
        'event' => 'change',
        'progress' => [
          'type' => 'none',
        ],
      ],
      '#empty_option' => $this->t('- Select a branch -'),
    ];
    $form['layout']['dropdowns_wrapper']['report_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Report'),
      '#options' => $report_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#required' => TRUE,
      '#empty_option' => $this->t('- Select a report -'),
    ];

    if ($is_uploader) {
      $form['layout']['dropdowns_wrapper']['coop_dropdown']['#default_value'] = key($coop_options);

      $form['layout']['dropdowns_wrapper']['branch_dropdown']['#default_value'] = key($branch_options);
    } else {
      $form['layout']['dropdowns_wrapper']['coop_dropdown']['#empty_option'] = $this->t('- Select a cooperative -');
      $form['layout']['dropdowns_wrapper']['branch_dropdown']['#empty_option'] = $this->t('- Select a branch -');
    }

    $max_filesize = 100 * 1024 * 1024;

    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV File'),
      '#upload_location' => 'public://branch-file-uploads/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv'],
        'FileSizeLimit' => ['fileLimit' => $max_filesize]
      ],
      '#required' => TRUE,
    ];
    $form['actions'] = [
      '#type' => 'actions',
      '#attributes' => ['class' => ['align-center-group']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Upload',
      '#attributes' => ['class' => ['others-upload-button']],
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $fids = $form_state->getValue('csv_file');
    $coop_dropdown = $form_state->getValue('coop_dropdown');
    $branch_dropdown = $form_state->getValue('branch_dropdown');
    $report_dropdown = $form_state->getValue('report_dropdown');

    $coop = Node::load($coop_dropdown);
    $branch = Node::load($branch_dropdown);
    $report = Node::load($report_dropdown);

    $coop_name = '';
    $provider_code = '';
    $branch_name = '';
    $branch_code = '';

    if ($coop) {
      $coop_name = $coop->get('field_coop_name')->value;
      $provider_code = $coop->get('field_cic_provider_code')->value; 
    }
    if ($branch) {
      $branch_name = $branch->get('field_branch_name')->value;
      $branch_code = $branch->get('field_branch_code')->value;
    }

    $report_name = $report ? $report->getTitle() : '';

    $file = File::load(reset($fids));
    $filename = $file->getFilename();
    
    if (!$file) {
        $this->messenger()->addError($this->t('Could not load the uploaded file.'));
        return;
    }
    
    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();

    date_default_timezone_set('Asia/Manila');
    $current_date = date('F j, Y');

    try {
        $file->setPermanent();
        $file->save();
    
        $values = [
            'type' => 'others_file_upload_history',
            'title' => "[$filename] Others File Upload History",
            'status' => 1,
            'field_branch' => $branch_dropdown,
            'field_branch_name' => $branch_name,
            'field_cooperative' => $coop_dropdown,
            'field_coop_name' => $coop_name,
            'field_date_uploaded' => $current_date,
            'field_file' => $file->id(),
            'field_file_name' => $filename,
            'field_report_type' => $report_dropdown,
            'field_report_type_name' => $report_name,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();

        $this->messenger()->addStatus($this->t('File upload successful!'));
    } catch (\Exception $e) {
        \Drupal::logger('Others File Upload')->error('Failed to create others file history node: @message', ['@message' => $e->getMessage()]);
        $this->messenger()->setError($this->t('An error occurred while creating the upload history record.'));
    }
    
    $data = [
      'changed_fields' => [],
    ];
    
    $action = 'Uploaded ' . $file->getFilename() . ' for ' . $provider_code . ' - ' . $branch_code;
    $this->activityLogger->log($action, 'node', NULL, $data, NULL, $this->currentUser);
  }
}
