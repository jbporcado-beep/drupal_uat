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

use Drupal\cooperative\Service\HeaderService;
use Drupal\cooperative\Service\IndividualService;
use Drupal\cooperative\Service\CompanyService;
use Drupal\cooperative\Service\InstallmentContractService;
use Drupal\cooperative\Service\NonInstallmentContractService;
use Drupal\cooperative\Service\FileHistoryService;


/**
 * Provides a simple upload form example with a submit handler.
 */

class UploadForm extends FormBase {
  /**
   * @var \Drupal\Core\Session\AccountProxyInterface
   */
  protected $currentUser;
  /**
   * @var \Drupal\cooperative\Service\HeaderService
   */
  protected $headerService;
  /**
   * @var \Drupal\cooperative\Service\IndividualService
   */
  protected $individualService;
  /**
   * @var \Drupal\cooperative\Service\CompanyService
   */
  protected $companyService;
  /**
   * @var \Drupal\cooperative\Service\InstallmentContractService
   */
  protected $installmentContractService;
  /**
   * @var \Drupal\cooperative\Service\NonInstallmentContractService
   */
  protected $nonInstallmentContractService;
    /**
   * @var \Drupal\cooperative\Service\FileHistoryService
   */
  protected $fileHistoryService;

  public function __construct(
    AccountProxyInterface $current_user, 
    HeaderService $headerService,
    IndividualService $individualService,
    CompanyService $companyService,
    InstallmentContractService $installmentContractService,
    NonInstallmentContractService $nonInstallmentContractService,
    FileHistoryService $fileHistoryService,
  ) {
    $this->currentUser = $current_user;
    $this->headerService = $headerService;
    $this->individualService = $individualService;
    $this->companyService = $companyService;
    $this->installmentContractService = $installmentContractService;
    $this->nonInstallmentContractService = $nonInstallmentContractService;
    $this->fileHistoryService = $fileHistoryService;
  }

  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('current_user'),
      $container->get('cooperative.header_service'),
      $container->get('cooperative.individual_service'),
      $container->get('cooperative.company_service'),
      $container->get('cooperative.installment_contract_service'),
      $container->get('cooperative.non_installment_contract_service'),
      $container->get('cooperative.file_history_service')
    );
  }

  private function branchExists(string $branch_code): bool {
    $field_name = 'field_branch_code';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition($field_name, $branch_code)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function doesBranchBelongToCoop(string $branch_code, string $provider_code): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition('field_branch_code', $branch_code)
      ->condition('field_branch_coop.entity.field_cic_provider_code', $provider_code)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function getCooperatives(): array {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'cooperative')
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $options[$node->get('field_cic_provider_code')->value] = $node->getTitle();
      }
    }
    return $options;
  }

  private function getBranches(): array {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->accessCheck(TRUE);
    $nids = $query->execute();
    if (!empty($nids)) {
      $nodes = $node_storage->loadMultiple($nids);
      foreach ($nodes as $node) {
        $options[$node->get('field_branch_code')->value] = $node->getTitle();
      }
    }
    return $options;
  }

  private function getReports(): array {
    $node_storage = \Drupal::entityTypeManager()->getStorage('node');
    $options = [];
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'report')
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

  public function exportErrors(array &$form, FormStateInterface $form_state) {
    $tempstore = \Drupal::service('tempstore.private')->get('errors_store');
    $error_array = $tempstore->get('validation_errors');
    $filename = $tempstore->get('current_file');

    if (!empty($error_array)) {
      $content = '[' . $filename . ']';
      $content .= "\n" . implode("\n", $error_array);
  
      $fileName = '[Errors] ' . date('Y-m-d_H-i-s') . '.txt';

      $tempstore->delete('validation_errors'); 
      $tempstore->delete('current_file'); 
  
      $response = new Response($content);
  
      $disposition = $response->headers->makeDisposition(
        ResponseHeaderBag::DISPOSITION_ATTACHMENT,
        $fileName
      );
      $response->headers->set('Content-Type', 'text/plain');
      $response->headers->set('Content-Disposition', $disposition);
  
      $form_state->setResponse($response); 

      return $response;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getFormId(): string {
    return 'cooperative_upload_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state): array {
    $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/file-upload-styles';
    $form['layout'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['form-page-layout']],
    ];
    $form['layout']['info_text'] = [
      '#type' => 'markup',
      '#markup' => '<h1>' . $this->t('Cooperative Branch File Upload') . '</h1>',
    ];


    $uid = \Drupal::currentUser()->id();
    $user = $uid > 0 ? User::load($uid) : NULL;
    $is_uploader = $user->hasRole('uploader') && !$user->hasRole('administrator') && !$user->hasRole('mass_specc_admin');

    $coop_options = $this->getCooperatives();
    $branch_options = $this->getBranches();
    $report_options = ["standard_credit_data" => "Standard Credit Data"];
    $report_options = $report_options + $this->getReports();

    if ($is_uploader) {
      $coop_entity = $user->get('field_cooperative')->referencedEntities();
      $coop = reset($coop_entity);
      $coop_options = [];
      if ($coop) {
        $coop_options[$coop->get('field_cic_provider_code')->value] = $coop->get('field_coop_name')->value;
        $coop_reports = $coop->get('field_assigned_report_templates')->referencedEntities();
        $report_options = ["standard_credit_data" => "Standard Credit Data"];
        foreach ($coop_reports as $report) {
          $report_options[$report->id()] = $report->getTitle();
        }
      }

      $branch_entity = $user->get('field_branch')->referencedEntities();
      $branch = reset($branch_entity);
      $branch_options = [];
      if ($branch) {
        $branch_options[$branch->get('field_branch_code')->value] = $branch->get('field_branch_name')->value;
      }
    }

    $form['layout']['dropdowns_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['class' => ['dropdowns-wrapper']],
    ];
    $form['layout']['dropdowns_wrapper']['coop_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Cooperative'),
      '#options' => $coop_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#required' => TRUE,
      '#disabled' => $is_uploader
    ];
    $form['layout']['dropdowns_wrapper']['branch_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Branch'),
      '#options' => $branch_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#disabled' => $is_uploader
    ];
    $form['layout']['dropdowns_wrapper']['report_dropdown'] = [
      '#type' => 'select',
      '#title' => $this->t('Report'),
      '#default_value' => "standard_credit_data",
      '#options' => $report_options,
      '#attributes' => ['class' => ['dropdown-item']],
      '#required' => TRUE,
      '#disabled' => TRUE
    ];

    if ($is_uploader) {
      $form['layout']['dropdowns_wrapper']['coop_dropdown']['#default_value'] = key($coop_options);

      $form['layout']['dropdowns_wrapper']['branch_dropdown']['#default_value'] = key($branch_options);
    }
    else  {
      $form['layout']['dropdowns_wrapper']['coop_dropdown']['#empty_option'] = $this->t('- Select a cooperative -');
      $form['layout']['dropdowns_wrapper']['branch_dropdown']['#empty_option'] = $this->t('- Select a branch -');
    }

    $max_filesize = 10485760;

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
    $form['actions']['verify'] = [
      '#type' => 'submit',
      '#value' => 'Verify',
      '#attributes' => ['class' => ['verify-button']],
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => 'Upload',
      '#attributes' => ['class' => ['upload-button']],
    ];

    $tempstore = \Drupal::service('tempstore.private')->get('errors_store');
    $errors_to_export = $tempstore->get('validation_errors');
    $filename = $tempstore->get('current_file');

    $error_display = [];


    if (!empty($errors_to_export)) {
      $form['actions']['export'] = [
        '#type' => 'submit',
        '#value' => $this->t('Download Error Logs'),
        '#weight' => 10,
        '#attributes' => [
          'class' => ['download-errors-button'],
          'onclick' => '
            this.classList.add("hidden");
        
            const errorsPresent = document.querySelector(".error-summary");
            if (errorsPresent) {
              errorsPresent.classList.add("error-summary-hidden");
            } 
          ',
        ],
        '#submit' => ['::exportErrors'],
        '#limit_validation_errors' => [],
      ];
      $error_count = count($errors_to_export);
      $errors_limit = 5;

      $error_display[] = [
        '#markup' => '<p>Filename: ' . $filename . '</p>',
        '#weight' => 10,
      ];

      $i = 0;
      foreach ($errors_to_export as $error_message) {
        if ($i >= $errors_limit) {
          break;
        }

        $error_display[] = [
          '#markup' => '<p>' . $error_message . '</p>',
          '#weight' => 10 + $i,
        ];
        $i++;
      }
      $form['submission_errors'] = [
        '#type' => 'container',
        '#attributes' => ['class' => ['error-summary']],
        '#weight' => 100,
        'content' => $error_display,
      ];
    }


    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $tempstore = \Drupal::service('tempstore.private')->get('errors_store');
    $tempstore->delete('validation_errors'); 
    $tempstore->delete('current_file'); 
    $fids = $form_state->getValue('csv_file');
    $coop_dropdown = $form_state->getValue('coop_dropdown');
    $branch_dropdown = $form_state->getValue('branch_dropdown');

    $current_user = \Drupal::currentUser();
    $user_id = $current_user->id();

    $trigger = $form_state->getTriggeringElement();                                                                         
    $clicked_value = $trigger['#value'];                                                                              
    $is_verify = ($clicked_value === 'Verify');

    if (empty($fids) || !is_array($fids)) {
      $this->messenger()->addError($this->t('No file uploaded.'));
      return;
    }

    $file = File::load(reset($fids));

    if (!$file) {
      $this->messenger()->addError($this->t('Could not load the uploaded file.'));
      return;
    }

    $uri = $file->getFileUri();
    $stream = fopen($uri, 'r');

    if (!$stream) {
      $this->messenger()->addError($this->t('Unable to open the CSV file.'));
      return;
    }

    // Normalize a header/field name to a canonical key.
    $normalize = static function (string $label): string {
      $label = trim(mb_strtolower($label));
      $label = str_replace(['_', '-',], ' ', $label);
      $label = str_replace(['/', '(', ')', '.', '\'', ':', '?'], '', $label);
      $label = preg_replace('/\s+/', ' ', $label);
      return trim($label);
    };

    // Read header.
    $header = fgetcsv($stream); //an array
    $normalized_header = array_map($normalize, $header);

    if ($header === FALSE) {
      $this->messenger()->addError($this->t('The CSV appears to be empty.'));
      fclose($stream);
      return;
    }

    // Stats and errors collection.
    $row_number = 1;
    $row_with_header = [];
    $errors = [];
    $filename = $file->getFilename();
    $report_type = $form_state->getValue('report_dropdown');

    if ($report_type === 'standard_credit_data') {
      
      if (!in_array('record type', $normalized_header)) {
        $errors[] = "MISSING 'RECORD TYPE' COLUMN IN THE CSV HEADER";
        $tempstore->set('validation_errors', $errors);
        $tempstore->set('current_file', $file->getFilename());
        fclose($stream);
        return;
      }

      if (!empty($branch_dropdown) && !$this->doesBranchBelongToCoop($branch_dropdown, $coop_dropdown)) {
        $errors[] = "SELECTED BRANCH DOES NOT BELONG TO THE SELECTED COOPERATIVE";
      }


      $connection = \Drupal::database();
      $transaction = $connection->startTransaction();
      
      while (($row = fgetcsv($stream)) !== FALSE) {
        $row_number++;
        $row_with_header = array_combine($normalized_header, $row);

        $record_type = trim((string) ($row_with_header['record type'] ?? ''));

        if (!in_array($record_type, ['ID', 'BD', 'CI', 'CN'])) {
          $errors[] = "Row $row_number | 30-009: 'RECORD TYPE' IS NOT VALID";
          continue;
        }

        try {
          $provider_code  = trim((string) ($row_with_header['provider code'] ?? ''));
          $branch_code    = trim((string) ($row_with_header['branch code'] ?? ''));
          $reference_date = trim((string) ($row_with_header['reference date'] ?? ''));

          if (!empty($branch_code) && !$this->branchExists($branch_code)) {
            $errors[] = "Row $row_number | THE BRANCH WITH BRANCH CODE $branch_code DOESN'T EXIST";
          }
          if ((string) $branch_dropdown !== (string) $branch_code) {
            $errors[] = "Row $row_number | BRANCH CODE INSIDE THE FILE IS NOT CONSISTENT WITH THE SELECTED BRANCH";
          }
          if (!empty($provider_code) && $coop_dropdown !== $provider_code) {
            $errors[] = "Row $row_number | PROVIDER CODE INSIDE THE FILE IS NOT CONSISTENT WITH THE SELECTED COOPERATIVE";
          }


          $this->headerService->import($row_with_header, $row_number, $errors);

          if ($record_type === 'ID') {
            $this->individualService->import($row_with_header, $row_number, $errors);
          }
          else if ($record_type === 'BD') {
            $this->companyService->import($row_with_header, $row_number, $errors);
          }
          else if ($record_type === 'CI') {
            $this->installmentContractService->import($row_with_header, $row_number, $errors);
          }
          else if ($record_type === 'CN') {
            $this->nonInstallmentContractService->import($row_with_header, $row_number, $errors);
          }
        }
        catch (\Throwable $e) {
          $errors[] = $this->t('Row @row: failed to save. @msg', [
            '@row' => $row_number,
            '@msg' => $e->getMessage(),
          ]);
        }
      }

      fclose($stream);

      if (!empty($errors)) {
        $transaction->rollBack();
        $tempstore->set('validation_errors', $errors);
        $tempstore->set('current_file', $file->getFilename());
        return;
      }

      if ($is_verify) {
        $transaction->rollBack();
        $this->messenger()->addStatus($this->t('File verified with no errors!'));
      }
      else {
        unset($transaction);
        $this->fileHistoryService->create($file, $row_with_header);
        $this->messenger()->addStatus($this->t('File upload successful!'));
      }
    }
  }
}
