<?php

namespace Drupal\cooperative\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

use Drupal\cooperative\Utility\FieldMaps;
use Drupal\cooperative\Utility\NodeHelper;
use Drupal\cooperative\Service\FileContentService;

/**
 * Provides a simple upload form example with a submit handler.
 */

class UploadForm extends FormBase {
  /**
   * @var \Drupal\cooperative\Service\FileContentService
   */
  protected $fileContentService;

  public function __construct(FileContentService $fileContentService) {
    $this->fileContentService = $fileContentService;
  }

  public static function create($container) {
    return new static(
      $container->get('cooperative.file_content_service')
    );
  }

  private function branchExists(string $branch_code): bool {
    $field_name = 'field_branch_code';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition($field_name, $branch_code)
      ->accessCheck(TRUE) // Check user permission
      ->range(0, 1); // Stop after finding one result

    $result = $query->execute();

    return !empty($result);
  }

  private function getHeader(string $provider_code, string $branch_code, string $reference_date): ?Node {

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'header')
      ->condition('field_provider_code', $provider_code)
      ->condition('field_header_branch_code', $branch_code)
      ->condition('field_reference_date', $reference_date)
      ->accessCheck(TRUE)
      ->range(0, 1);
    $result = $query->execute();
    if (!empty($result)) {
      $nid = reset($result);
      $node = Node::load($nid);
      return $node;
    }
    return null;
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
    $max_filesize = 10485760;

    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV file'),
      '#upload_location' => 'temporary://cooperative_uploads/',
      '#upload_validators' => [
        'FileExtension' => ['extensions' => 'csv'],
        'FileSizeLimit' => ['fileLimit' => $max_filesize]
      ],
      '#required' => TRUE,
      '#description' => $this->t('Upload a CSV file'),
    ];
    $form['actions'] = [
      '#type' => 'actions',
    ];
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Submit'),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state): void {
    $fids = $form_state->getValue('csv_file');

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

    if ($header === FALSE) {
      $this->messenger()->addError($this->t('The CSV appears to be empty.'));
      fclose($stream);
      return;
    }

    // Build header map (normalized label => index).
    $map = [];
    foreach ($header as $idx => $label) { // Separate $header into $idx (column number) and $label (header name)
      $map[$normalize((string) $label)] = $idx; // call the normalize function passing the header name and store it in $map
    }

    // Stats and errors collection.
    $row_number = 1; // Counting from 1 for header already read; data starts at 2.
    $errors = [];

    if (!isset($map['record type'])) {
      $this->messenger()->addError($this->t('Missing "record type" column in CSV header.'));
      fclose($stream);
      return;
    }

    $connection = \Drupal::database();
    $transaction = $connection->startTransaction();

    while (($row = fgetcsv($stream)) !== FALSE) {
      $row_number++;

      $record_type = trim((string) ($row[$map['record type']] ?? ''));

      if (!in_array($record_type, ['ID', 'BD', 'CI', 'CN'])) {
        $errors[] = $this->t('Row @row: invalid record type "@type".', [
          '@row' => $row_number,
          '@type' => $record_type,
        ]);
        continue;
      }

      try {
        // Extract values by header map.
        // $map['field'] returns the col no. of the field
        $provider_code  = trim((string) ($row[$map['provider code']] ?? ''));
        $branch_code    = trim((string) ($row[$map['branch code']] ?? ''));
        $reference_date = trim((string) ($row[$map['reference date']] ?? ''));

        if (!empty($branch_code) && !$this->branchExists($branch_code)) {
          $this->messenger()->addError($this->t("The branch with branch code $branch_code doesn't exist"));
          fclose($stream);
          return;
        }

        $header_node = $this->getHeader($provider_code, $branch_code, $reference_date);
        if (is_null($header_node)) {
          $header_title = "($provider_code - $branch_code) $reference_date";
          $header_node = NodeHelper::createNodeFromMap(
            'header', FieldMaps::HEADER_FIELD_MAP, $row, $row_number, $map, $header_title, $errors
          );
        }

        if ($record_type === 'ID') {
          $this->fileContentService->createIndividual($row, $row_number, $map, $errors);
        }
        else if ($record_type === 'BD') {
          $this->fileContentService->createCompany($row, $row_number, $map, $errors);
        }
        else if ($record_type === 'CI') {
          $this->fileContentService->createInstallment($header_node, $row, $row_number, $map, $errors);
        }
        else if ($record_type === 'CN') {
          $this->fileContentService->createNonInstallment($header_node, $row, $row_number, $map, $errors);
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

    // Report results.
    if (empty($errors)) {
      unset($transaction); //Commit the transaction
      $this->messenger()->addStatus($this->t('File upload successful!'));
    }
    else {
      $transaction->rollBack();
      foreach ($errors as $msg) {
        $this->messenger()->addError($msg);
      }
    }
  }
}
