<?php

namespace Drupal\cooperative\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\file\Entity\File;
use Drupal\node\Entity\Node;

/**
 * Provides a simple upload form example with a submit handler.
 */
class UploadForm extends FormBase {

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
    // CSV upload field.
    $form['csv_file'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('CSV file'),
      '#upload_location' => 'temporary://cooperative_uploads/',
      '#upload_validators' => [
        'file_validate_extensions' => ['csv'],
      ],
      '#required' => TRUE,
      '#description' => $this->t('Upload a CSV with headers: First name, Middle name, Last name, Email.'),
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
      $label = preg_replace('/\s+/', ' ', $label);
      $label = str_replace(['_', '-'], ' ', $label);
      return trim($label);
    };

    // Read header.
    $header = fgetcsv($stream);
    if ($header === FALSE) {
      $this->messenger()->addError($this->t('The CSV appears to be empty.'));
      fclose($stream);
      return;
    }

    // Build header map (normalized label => index).
    $map = [];
    foreach ($header as $idx => $label) {
      $map[$normalize((string) $label)] = $idx;
    }
    // Assumed field machine names on cooperative_member content type.
    $bundle = 'cooperative_member';
    $field_map = [
      'first name' => 'field_first_name',
      'middle name' => 'field_middle_name',
      'last name' => 'field_last_name',
      'email' => 'field_email',
    ];

    // Stats and errors collection.
    $row_number = 1; // Counting from 1 for header already read; data starts at 2.
    $created = 0;
    $errors = [];

    while (($row = fgetcsv($stream)) !== FALSE) {
      $row_number++;

      // Extract values by header map.
      $first = trim((string) ($row[$map['first name']] ?? ''));
      $middle = trim((string) ($row[$map['middle name']] ?? ''));
      $last = trim((string) ($row[$map['last name']] ?? ''));
      $email = trim((string) ($row[$map['email']] ?? ''));

      // Prepare node values.
      $values = [
        'type' => $bundle,
        'title' => trim($first . ' ' . ($middle ? $middle . ' ' : '') . $last),
        'status' => 1,
      ];
      $values[$field_map['first name']] = $first;
      $values[$field_map['middle name']] = $middle;
      $values[$field_map['last name']] = $last;
      $values[$field_map['email']] = $email;

      // Create an entity and use Node API validation so field constraints apply.
      $node = Node::create($values);
      $violations = $node->validate();

      if ($violations->count() > 0) {
        foreach ($violations as $violation) {
          $errors[] = $this->t('Row @row: @message', [
            '@row' => $row_number,
            '@message' => $violation->getMessage(),
          ]);
        }
        continue;
      }

      try {
        $node->save();
        $created++;
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
    if ($created > 0) {
      $this->messenger()->addStatus($this->t('@count row(s) created.', [
        '@count' => $created,
      ]));
    }
    if (!empty($errors)) {
      foreach ($errors as $msg) {
        // Each $msg is already a TranslatableMarkup.
        $this->messenger()->addError($msg);
      }
    }
  }
}
