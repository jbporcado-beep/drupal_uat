<?php

namespace Drupal\cooperative\Utility;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Validation\IndividualValidator;
use Drupal\cooperative\Validation\FamilyValidator;
use Drupal\cooperative\Validation\AddressValidator;
use Drupal\cooperative\Validation\IdentificationValidator;
use Drupal\cooperative\Validation\ContactValidator;
use Drupal\cooperative\Validation\EmploymentValidator;
use Drupal\cooperative\Validation\CompanyValidator;
use Drupal\cooperative\Validation\InstallmentContractValidator;
use Drupal\cooperative\Validation\NonInstallmentContractValidator;


class NodeHelper {

  private static function validate(string $type, Node $node, array &$errors, int $row_number, string $record_type) {
    if ($type === 'individual') {
      IndividualValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'family') {
      FamilyValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'address') {
      AddressValidator::validate($node, $errors, $row_number, $record_type);
    }
    else if ($type === 'identification') {
      IdentificationValidator::validate($node, $errors, $row_number, $record_type);
    }
    else if ($type === 'contact') {
      ContactValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'employment') {
      EmploymentValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'company') {
      CompanyValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'installment_contract') {
      InstallmentContractValidator::validate($node, $errors, $row_number);
    }
    else if ($type === 'noninstallment_contract') {
      NonInstallmentContractValidator::validate($node, $errors, $row_number);
    }
  }
  
  public static function createNodeFromMap(
    string $type, array $field_map, array $row, int $row_number, array $map, array &$errors, Node $header_node = null
    ): ?Node {
    $values = [
      'type' => $type,
      'title' => 'Header',
      'status' => 1,
    ];
    foreach ($field_map as $normalized_label => $field_machine_name) {
      $values[$field_machine_name] = trim((string) ($row[$map[$normalized_label] ?? ''] ?? ''));
    }
    $node = Node::create($values);

    if ($type === 'header') {
      $node->set('field_version', '1.0');
      $node->set('field_submission_type', '1');
    }

    if ($type === 'individual') {
      $number_of_dependants = trim((string) ($row[$map['number of dependents'] ?? $map['number of dependants']] ?? ''));
      $date_of_birth        = trim((string) ($row[$map['date of birth']] ?? ''));
      $node->set('field_number_of_dependants', $number_of_dependants);
      if (strlen($date_of_birth) === 7) {
        $node->set('field_date_of_birth', '0' . $date_of_birth);
      }
    }

    if ($type === 'family') {
      $mother_maiden_fullname = trim((string) ($row[$map['mother maiden full name'] ?? $map['mothers maiden full name']] ?? ''));
      $node->set('field_mother_maiden_full_name', $mother_maiden_fullname);
    }

    if ($type === 'identification') {
      // Cast to int so that scientific notation is converted to full number
      $identification_1_number = trim((int) ($row[$map['identification 1 number']] ?? ''));
      $identification_2_number = trim((int) ($row[$map['identification 2 number']] ?? ''));

      $id1_issuedate   = trim((string) ($row[$map['id 1 issuedate']] ?? ''));
      $id_1_expirydate = trim((string) ($row[$map['id 1 expirydate']] ?? ''));

      $id2_issuedate   = trim((string) ($row[$map['id 2 issuedate']] ?? ''));
      $id_2_expirydate = trim((string) ($row[$map['id 2 expirydate']] ?? ''));

      $node->set('field_identification1_number', $identification_1_number);
      $node->set('field_identification2_number', $identification_2_number);

      if (strlen($id1_issuedate) === 7) {
        $node->set('field_id1_issuedate', '0' . $id1_issuedate);
      }
      if (strlen($id_1_expirydate) === 7) {
        $node->set('field_id1_expirydate', '0' . $id_1_expirydate);
      }
      if (strlen($id2_issuedate) === 7) {
        $node->set('field_id2_issuedate', '0' . $id2_issuedate);
      }
      if (strlen($id_2_expirydate) === 7) {
        $node->set('field_id2_expirydate', '0' . $id_2_expirydate);
      }
      return null;
    }

    if ($type === 'installment_contract' || $type === 'noninstallment_contract') {
      $node->set('field_header', $header_node);

      $contract_start_date        = trim((string) ($row[$map['contract start date']] ?? ''));
      $contract_end_actual_date   = trim((string) ($row[$map['contract end actual date']] ?? ''));
      $contract_end_planned_date  = trim((string) ($row[$map['contract end planned date']] ?? ''));

      if (strlen($contract_start_date) === 7) {
        $node->set('field_contract_start_date', '0' . $contract_start_date);
      }
      if (strlen($contract_end_actual_date) === 7) {
        $node->set('field_contract_end_actual_date', '0' . $contract_end_actual_date);
      }
      if (strlen($contract_end_planned_date) === 7) {
        $node->set('field_contract_end_planned_date', '0' . $contract_end_planned_date);
      }
    }

    if ($type === 'installment_contract') {
      $next_payment_date = trim((string) ($row[$map['next payment date']] ?? ''));

      if (strlen($next_payment_date) === 7) {
        $node->set('field_next_payment_date', '0' . $next_payment_date);
      }
    }

    $record_type = trim((string) ($row[$map['record type']] ?? ''));

    $violations = $node->validate();
    self::validate($type, $node, $errors, $row_number, $record_type);
    foreach ($violations as $violation) {
      continue;
    }

    $node->save();

    return $node;
  }

  public static function updateNodeFromMap(
    Node $node, array $field_map, array $row, int $row_number, array $map, array &$errors, Node $header_node = null, string $title = ''
    ): ?Node {
    foreach ($field_map as $normalized_label => $field_machine_name) {
      $node->set($field_machine_name, trim((string) ($row[$map[$normalized_label] ?? ''] ?? '')));
    }
    $type = $node->getType();
    $record_type = trim((string) ($row[$map['record type']] ?? ''));

    if ($type === 'individual') {
      $date_of_birth = trim((string) ($row[$map['date of birth']] ?? ''));
      if (strlen($date_of_birth) === 7) {
        $node->set('field_date_of_birth', '0' . $date_of_birth);
      }
    }

    if ($type === 'installment_contract' || $type === 'noninstallment_contract') {
      $node->set('field_header', $header_node);

      $contract_start_date        = trim((string) ($row[$map['contract start date']] ?? ''));
      $contract_end_actual_date   = trim((string) ($row[$map['contract end actual date']] ?? ''));
      $contract_end_planned_date  = trim((string) ($row[$map['contract end planned date']] ?? ''));
      if (strlen($contract_start_date) === 7) {
        $node->set('field_contract_start_date', '0' . $contract_start_date);
      }
      if (strlen($contract_end_actual_date) === 7) {
        $node->set('field_contract_end_actual_date', '0' . $contract_end_actual_date);
      }
      if (strlen($contract_end_planned_date) === 7) {
        $node->set('field_contract_end_planned_date', '0' . $contract_end_planned_date);
      }
    }

    if ($type === 'installment_contract') {
      $next_payment_date = trim((string) ($row[$map['next payment date']] ?? ''));

      if (strlen($next_payment_date) === 7) {
        $node->set('field_next_payment_date', '0' . $next_payment_date);
      }
    }

    $violations = $node->validate();
    self::validate($type, $node, $errors, $row_number, $record_type);
    foreach ($violations as $violation) {
      continue;
    }

    if (empty($errors)) {
      $node->save();
    }
    return $node;
  }
}
