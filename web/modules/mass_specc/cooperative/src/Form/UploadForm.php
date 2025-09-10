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

  private function branch_code_exists(string $branch_code): bool {
    $field_name = 'field_branch_code';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'branch')
      ->condition($field_name, $branch_code)
      ->accessCheck(TRUE) // Check user permission
      ->range(0, 1); // Stop after finding one result

    $result = $query->execute();

    return !empty($result);
  }

  private function provider_subj_no_exists(string $provider_subj_no): bool {
    $field_name = 'field_provider_subject_no';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'individual')
      ->condition($field_name, $provider_subj_no)
      ->accessCheck(TRUE) // Check user permission
      ->range(0, 1); // Stop after finding one result

    $result = $query->execute();

    return !empty($result);
  }

  private function header_exists(string $provider_code, string $branch_code, string $subj_reference_date): bool {

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'header')
      ->condition('field_provider_code', $provider_code)
      ->condition('field_header_branch_code', $branch_code)
      ->condition('field_subject_reference_date', $subj_reference_date)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasFamily(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'family')
      ->condition('field_family_provider_subj_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasAddress(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'address')
      ->condition('field_address_provider_subj_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasIdentification(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'identification')
      ->condition('field_identity_provider_subj_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasContact(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_contact_provider_subj_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasEmployment(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'employment')
      ->condition('field_employ_provider_subj_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
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


    // Assumed field machine names on customer content type.
    $header_field_map = [
      'provider code'           => 'field_provider_code',
      'branch code'             => 'field_header_branch_code',
      'subject reference date'  => 'field_subject_reference_date',
    ];

    $individual_field_map = [
      'provider subject no'     => 'field_provider_subject_no',
      'title'                   => 'field_title',
      'first name'              => 'field_first_name',
      'last name'               => 'field_last_name',
      'middle name'             => 'field_middle_name',
      'suffix'                  => 'field_suffix',
      'previous last name'      => 'field_previous_last_name',
      'gender'                  => 'field_gender',
      'date of birth'           => 'field_date_of_birth',
      'place of birth'          => 'field_place_of_birth',
      'country of birth code'   => 'field_country_of_birth_code',
      'nationality'             => 'field_nationality',
      'resident'                => 'field_resident',
      'civil status'            => 'field_civil_status',
      'number of dependants'    => 'field_number_of_dependants',
      'cars owned'              => 'field_cars_owned',
    ];

    $family_field_map = [
      'provider subject no'     => 'field_family_provider_subj_no',
      'spouse first name'       => 'field_spouse_first_name',
      'spouse last name'        => 'field_spouse_last_name',
      'spouse middle name'      => 'field_spouse_middle_name',
      'mother maiden full name' => 'field_mother_maiden_full_name',
      'father first name'       => 'field_father_first_name',
      'father last name'        => 'field_father_last_name',
      'father middle name'      => 'field_father_middle_name',
      'father suffix'           => 'field_father_suffix',
    ];

    $address_field_map = [
      'provider subject no'     => 'field_address_provider_subj_no',
      'address 1 address type'  => 'field_address1_type',
      'address 1 fulladdress'   => 'field_address1_fulladdress',
      'address 2 address type'  => 'field_address2_type',
      'address 2 fulladdress'   => 'field_address2_fulladdress',
    ];

    $identification_field_map = [
      'provider subject no'     => 'field_identity_provider_subj_no',
      'identification 1 type'   => 'field_identification1_type',
      'identification 1 number' => 'field_identification1_number',
      'identification 2 type'   => 'field_identification2_type',
      'identification 2 number' => 'field_identification2_number',
      'id 1 type'               => 'field_id1_type',
      'id 1 number'             => 'field_id1_number',
      'id 1 issuedate'          => 'field_id1_issuedate',
      'id 1 issuecountry'       => 'field_id1_issuecountry',
      'id 1 expirydate'         => 'field_id1_expirydate',
      'id 1 issued by'          => 'field_id1_issuedby',
      'id 2 type'               => 'field_id2_type',
      'id 2 number'             => 'field_id2_number',
      'id 2 issuedate'          => 'field_id2_issuedate',
      'id 2 issuecountry'       => 'field_id2_issuecountry',
      'id 2 expirydate'         => 'field_id2_expirydate',
      'id 2 issued by'          => 'field_id2_issuedby',
    ];

    $contact_field_map = [
      'provider subject no'     => 'field_contact_provider_subj_no',
      'contact 1 type'          => 'field_contact1_type',
      'contact 1 value'         => 'field_contact1_value',
      'contact 2 type'          => 'field_contact2_type',
      'contact 2 value'         => 'field_contact2_value',
    ];

    $employment_field_map = [
      'provider subject no'         => 'field_employ_provider_subj_no',
      'employment trade name'       => 'field_employ_trade_name',
      'employment psic'             => 'field_employ_psic',
      'employment occupationstatus' => 'field_employ_occupation_status',
      'employment occupation'       => 'field_employ_occupation',
    ];

    // Stats and errors collection.
    $row_number = 1; // Counting from 1 for header already read; data starts at 2.
    $created = 0;
    $errors = [];

    $connection = \Drupal::database();
    $transaction = $connection->startTransaction();

    while (($row = fgetcsv($stream)) !== FALSE) {
      $row_number++;

      try {
        // Extract values by header map.
        // $map['field'] returns the col no. of the field
        $provider_code        = trim((string) ($row[$map['provider code']] ?? ''));
        $branch_code          = trim((string) ($row[$map['branch code']] ?? ''));
        $subj_reference_date  = trim((string) ($row[$map['subject reference date']] ?? ''));

        if (!$this->header_exists($provider_code, $branch_code, $subj_reference_date)) {

          if (!$this->branch_code_exists($branch_code)) {
            $this->messenger()->addError($this->t("The branch with branch code $branch_code doesn't exist"));
            fclose($stream);
            return;
          }

          // Prepare node values.
          $header_values = [
            'type' => 'header',
            'title' => "($provider_code - $branch_code) $subj_reference_date",
            'status' => 1,
          ];

          foreach ($header_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider code':
                $header_values[$field_machine_name] = $provider_code;
                break;
              case 'branch code':
                $header_values[$field_machine_name] = $branch_code;
                break;
              case 'subject reference date':
                $header_values[$field_machine_name] = $subj_reference_date;
                break;
            }
          }

          // Create an entity and use Node API validation so field constraints apply.
          $header_node = Node::create($header_values);
          $header_node->save();
          $created++;

          $violations = $header_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
        }

        // Individual

        // TODO: add an error to errors[] when a field doesnt match here
        $provider_subj_no        = trim((string) ($row[$map['provider subject no']] ?? ''));
        if (!$this->provider_subj_no_exists($provider_subj_no)) {
          $title                 = trim((string) ($row[$map['title']] ?? ''));
          $first_name            = trim((string) ($row[$map['first name']] ?? ''));
          $last_name             = trim((string) ($row[$map['last name']] ?? ''));
          $middle_name           = trim((string) ($row[$map['middle name']] ?? ''));
          $suffix                = trim((string) ($row[$map['suffix']] ?? ''));
          $previous_last_name    = trim((string) ($row[$map['previous last name']] ?? ''));
          $gender                = trim((string) ($row[$map['gender']] ?? ''));
          $date_of_birth         = trim((string) ($row[$map['date of birth']] ?? ''));
          $place_of_birth        = trim((string) ($row[$map['place of birth']] ?? ''));
          $country_of_birth_code = trim((string) ($row[$map['country of birth code']] ?? ''));
          $nationality           = trim((string) ($row[$map['nationality']] ?? ''));
          $resident              = trim((string) ($row[$map['resident']] ?? ''));
          $civil_status          = trim((string) ($row[$map['civil status']] ?? ''));
          $number_of_dependants  = trim((string) ($row[$map['number of dependants'] ?? $map['number of dependents']] ?? ''));
          $cars_owned            = trim((string) ($row[$map['cars owned']] ?? ''));

          $individual_values = [
            'type' => 'individual',
            'title' => "[$provider_subj_no] $first_name $last_name",
            'status' => 1,
          ];
  
          foreach ($individual_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider subject no':
                $individual_values[$field_machine_name] = $provider_subj_no;
                break;
              case 'title':
                $individual_values[$field_machine_name] = $title;
                break;
              case 'first name':
                $individual_values[$field_machine_name] = $first_name;
                break;
              case 'last name':
                $individual_values[$field_machine_name] = $last_name;
                break;
              case 'middle name':
                $individual_values[$field_machine_name] = $middle_name;
                break;
              case 'suffix':
                $individual_values[$field_machine_name] = $suffix;
                break;
              case 'previous last name':
                $individual_values[$field_machine_name] = $previous_last_name;
                break;
              case 'gender':
                $individual_values[$field_machine_name] = $gender;
                break;
              case 'date of birth':
                $individual_values[$field_machine_name] = $date_of_birth;
                break;
              case 'place of birth':
                $individual_values[$field_machine_name] = $place_of_birth;
                break;
              case 'country of birth code':
                $individual_values[$field_machine_name] = $country_of_birth_code;
                break;
              case 'nationality':
                $individual_values[$field_machine_name] = $nationality;
                break;
              case 'resident':
                $individual_values[$field_machine_name] = $resident;
                break;
              case 'civil status':
                $individual_values[$field_machine_name] = $civil_status;
                break;
              case 'number of dependants':
                $individual_values[$field_machine_name] = $number_of_dependants;
                break;
              case 'cars owned':
                $individual_values[$field_machine_name] = $cars_owned;
                break;
            }
          }
  
          $individual_node = Node::create($individual_values);
          $individual_node->save();
          $created++;
  
          $violations = $individual_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
        }

        // FAMILY
        // Only create if all the fields aren't empty - if at least one field has a value

        if (!$this->individualHasFamily($provider_subj_no)) {
          $spouse_first_name         = trim((string) ($row[$map['spouse first name']] ?? ''));
          $spouse_last_name          = trim((string) ($row[$map['spouse last name']] ?? ''));
          $spouse_middle_name        = trim((string) ($row[$map['spouse middle name']] ?? ''));
          $mother_maiden_full_name   = trim((string) ($row[$map['mother maiden full name'] ?? $map['mothers maiden full name']] ?? ''));
          $father_first_name         = trim((string) ($row[$map['father first name']] ?? ''));
          $father_last_name          = trim((string) ($row[$map['father last name']] ?? ''));
          $father_middle_name        = trim((string) ($row[$map['father middle name']] ?? ''));
          $father_suffix             = trim((string) ($row[$map['father suffix']] ?? ''));
  
          $family_fields = [
            $spouse_first_name, $spouse_last_name, $spouse_middle_name, $mother_maiden_full_name, 
            $father_first_name, $father_last_name, $father_middle_name, $father_suffix
          ];
  
          $isFamilyEmpty = empty(array_filter($family_fields));
  
          // Check if individual is connected to a family. If it is, get that node and look to update it if any values are different
          // Prepare node values.
          if (!$isFamilyEmpty) {
            $family_values = [
              'type' => 'family',
              'title' => "[$provider_subj_no] Family",
              'status' => 1,
            ];
    
            foreach ($family_field_map as $normalized_label => $field_machine_name) {
              switch ($normalized_label) {
                case 'provider subject no':
                  $family_values[$field_machine_name] = $provider_subj_no;
                  break;
                case 'spouse first name':
                  $family_values[$field_machine_name] = $spouse_first_name;
                  break;
                case 'spouse last name':
                  $family_values[$field_machine_name] = $spouse_last_name;
                  break;
                case 'spouse middle name':
                  $family_values[$field_machine_name] = $spouse_middle_name;
                  break;
                case 'mother maiden full name':
                  $family_values[$field_machine_name] = $mother_maiden_full_name;
                  break;
                case 'father first name':
                  $family_values[$field_machine_name] = $father_first_name;
                  break;
                case 'father last name':
                  $family_values[$field_machine_name] = $father_last_name;
                  break;
                case 'father middle name':
                  $family_values[$field_machine_name] = $father_middle_name;
                  break;
                case 'father suffix':
                  $family_values[$field_machine_name] = $father_suffix;
                  break;
              }
            }
    
            $family_node = Node::create($family_values);
            $family_node->save();
            $created++;
    
            $violations = $family_node->validate();
            if ($violations->count() > 0) {
              foreach ($violations as $violation) {
                $errors[] = $this->t('Row @row: @message', [
                  '@row' => $row_number,
                  '@message' => $violation->getMessage(),
                ]);
              }
              continue;
            }
          }
        }

        // ADDRESS
        if (!$this->individualHasAddress($provider_subj_no)) {
          $address1_type         = trim((string) ($row[$map['address 1 address type']] ?? ''));
          $address1_fulladdress  = trim((string) ($row[$map['address 1 fulladdress']] ?? ''));
          $address2_type         = trim((string) ($row[$map['address 2 address type']] ?? ''));
          $address2_fulladdress  = trim((string) ($row[$map['address 2 fulladdress']] ?? ''));
  
          $address_values = [
            'type' => 'address',
            'title' => "[$provider_subj_no] Address",
            'status' => 1,
          ];
  
          foreach ($address_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider subject no':
                $address_values[$field_machine_name] = $provider_subj_no;
                break;
              case 'address 1 address type':
                $address_values[$field_machine_name] = $address1_type;
                break;
              case 'address 1 fulladdress':
                $address_values[$field_machine_name] = $address1_fulladdress;
                break;
              case 'address 2 address type':
                $address_values[$field_machine_name] = $address2_type;
                break;
              case 'address 2 fulladdress':
                $address_values[$field_machine_name] = $address2_fulladdress;
                break;
            }
          }
  
          $address_node = Node::create($address_values);
          $address_node->save();
          $created++;
  
          $violations = $address_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
        }

        // IDENTIFICATION

        if (!$this->individualHasIdentification($provider_subj_no)) {
          $identification1_type   = trim((string) ($row[$map['identification 1 type']] ?? ''));
          $identification1_number = trim((string) ($row[$map['identification 1 number']] ?? ''));
          $identification2_type   = trim((string) ($row[$map['identification 2 type']] ?? ''));
          $identification2_number = trim((string) ($row[$map['identification 2 number']] ?? ''));
          $id1_type               = trim((string) ($row[$map['id 1 type']] ?? ''));
          $id1_number             = trim((string) ($row[$map['id 1 number']] ?? ''));
          $id1_issuedate          = trim((string) ($row[$map['id 1 issuedate']] ?? ''));
          $id1_issuecountry       = trim((string) ($row[$map['id 1 issuecountry']] ?? ''));
          $id1_expirydate         = trim((string) ($row[$map['id 1 expirydate']] ?? ''));
          $id1_issuedby           = trim((string) ($row[$map['id 1 issued by']] ?? ''));
          $id2_type               = trim((string) ($row[$map['id 2 type']] ?? ''));
          $id2_number             = trim((string) ($row[$map['id 2 number']] ?? ''));
          $id2_issuedate          = trim((string) ($row[$map['id 2 issuedate']] ?? ''));
          $id2_issuecountry       = trim((string) ($row[$map['id 2 issuecountry']] ?? ''));
          $id2_expirydate         = trim((string) ($row[$map['id 2 expirydate']] ?? ''));
          $id2_issuedby           = trim((string) ($row[$map['id 2 issued by']] ?? ''));

          $identification_values = [
            'type' => 'identification',
            'title' => "[$provider_subj_no] Identification",
            'status' => 1,
          ];
  
          foreach ($identification_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider subject no':
                $identification_values[$field_machine_name] = $provider_subj_no;
                break;
              case 'identification 1 type':
                $identification_values[$field_machine_name] = $identification1_type;
                break;
              case 'identification 1 number':
                $identification_values[$field_machine_name] = $identification1_number;
                break;
              case 'identification 2 type':
                $identification_values[$field_machine_name] = $identification2_type;
                break;
              case 'identification 2 number':
                $identification_values[$field_machine_name] = $identification2_number;
                break;
              case 'id 1 type':
                $identification_values[$field_machine_name] = $id1_type;
                break;
              case 'id 1 number':
                $identification_values[$field_machine_name] = $id1_number;
                break;
              case 'id 1 issuedate':
                $identification_values[$field_machine_name] = $id1_issuedate;
                break;
              case 'id 1 issuecountry':
                $identification_values[$field_machine_name] = $id1_issuecountry;
                break;
              case 'id 1 expirydate':
                $identification_values[$field_machine_name] = $id1_expirydate;
                break;
              case 'id 1 issued by':
                $identification_values[$field_machine_name] = $id1_issuedby;
                break;
              case 'id 2 type':
                $identification_values[$field_machine_name] = $id2_type;
                break;
              case 'id 2 number':
                $identification_values[$field_machine_name] = $id2_number;
                break;
              case 'id 2 issuedate':
                $identification_values[$field_machine_name] = $id2_issuedate;
                break;
              case 'id 2 issuecountry':
                $identification_values[$field_machine_name] = $id2_issuecountry;
                break;
              case 'id 2 expirydate':
                $identification_values[$field_machine_name] = $id2_expirydate;
                break;
              case 'id 2 issued by':
                $identification_values[$field_machine_name] = $id2_issuedby;
                break;
            }
          }
  
          $identification_node = Node::create($identification_values);
          $identification_node->save();
          $created++;
  
          $violations = $identification_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
        }

        // CONTACT
        if (!$this->individualHasContact($provider_subj_no)) {
          $contact1_type  = trim((string) ($row[$map['contact 1 type']] ?? ''));
          $contact1_value = trim((string) ($row[$map['contact 1 value']] ?? ''));
          $contact2_type  = trim((string) ($row[$map['contact 2 type'] ?? ''] ?? ''));
          $contact2_value = trim((string) ($row[$map['contact 2 value'] ?? ''] ?? ''));
          
          $contact_values = [
            'type' => 'contact',
            'title' => "[$provider_subj_no] Contact",
            'status' => 1,
          ];
  
          foreach ($contact_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider subject no':
                $contact_values[$field_machine_name] = $provider_subj_no;
                break;
              case 'contact 1 type':
                $contact_values[$field_machine_name] = $contact1_type;
                break;
              case 'contact 1 value':
                $contact_values[$field_machine_name] = $contact1_value;
                break;
              case 'contact 2 type':
                $contact_values[$field_machine_name] = $contact2_type;
                break;
              case 'contact 2 value':
                $contact_values[$field_machine_name] = $contact2_value;
                break;
            }
          }
  
          $contact_node = Node::create($contact_values);
          $contact_node->save();
          $created++;
  
          $violations = $contact_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
        }
        

        // EMPLOYMENT
        if (!$this->individualHasEmployment($provider_subj_no)) {
          $employ_trade_name        = trim((string) ($row[$map['employment trade name']] ?? ''));
          $employ_psic              = trim((string) ($row[$map['employment psic']] ?? ''));
          $employ_occupation_status = trim((string) ($row[$map['employment occupationstatus'] ?? ''] ?? ''));
          $employ_occupation        = trim((string) ($row[$map['employment occupation'] ?? ''] ?? ''));
          
          $employment_values = [
            'type' => 'employment',
            'title' => "[$provider_subj_no] Employment",
            'status' => 1,
          ];
  
          foreach ($employment_field_map as $normalized_label => $field_machine_name) {
            switch ($normalized_label) {
              case 'provider subject no':
                $employment_values[$field_machine_name] = $provider_subj_no;
                break;
              case 'employment trade name':
                $employment_values[$field_machine_name] = $employ_trade_name;
                break;
              case 'employment psic':
                $employment_values[$field_machine_name] = $employ_psic;
                break;
              case 'employment occupationstatus':
                $employment_values[$field_machine_name] = $employ_occupation_status;
                break;
              case 'employment occupation':
                $employment_values[$field_machine_name] = $employ_occupation;
                break;
            }
          }
  
          $employment_node = Node::create($employment_values);
          $employment_node->save();
          $created++;
  
          $violations = $employment_node->validate();
          if ($violations->count() > 0) {
            foreach ($violations as $violation) {
              $errors[] = $this->t('Row @row: @message', [
                '@row' => $row_number,
                '@message' => $violation->getMessage(),
              ]);
            }
            continue;
          }
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
      if ($created > 0) {
        $this->messenger()->addStatus($this->t('@count row(s) created.', [
          '@count' => $created,
        ]));
      }
    }
    // TODO: Test if transaction rollsback when errors[] isnt empty
    else {
      $transaction->rollBack();
      foreach ($errors as $msg) {
        $this->messenger()->addError($msg);
      }
    }
  }
}
