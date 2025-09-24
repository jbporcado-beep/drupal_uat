<?php

namespace Drupal\cooperative\Service;

use Drupal\node\Entity\Node;

use Drupal\cooperative\Utility\FieldMaps;
use Drupal\cooperative\Utility\NodeHelper;

class FileContentService {
  
  private function getIndividual(string $provider_subj_no): ?Node {
    $field_name = 'field_provider_subject_no';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'individual')
      ->condition($field_name, $provider_subj_no)
      ->accessCheck(TRUE) // Check user permission
      ->range(0, 1); // Stop after finding one result

    $result = $query->execute();
    if (!empty($result)) {
      $nid = reset($result);
      $node = Node::load($nid);
      return $node;
    }
    return null;
  }

  private function getCompany(string $provider_subj_no): ?Node {
    $field_name = 'field_provider_subject_no';

    $query = \Drupal::entityQuery('node')
      ->condition('type', 'company')
      ->condition($field_name, $provider_subj_no)
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
  
  private function individualHasFamily(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'family')
      ->condition('field_provider_subject_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasAddress(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'address')
      ->condition('field_provider_subject_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasIdentification(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'identification')
      ->condition('field_provider_subject_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasContact(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'contact')
      ->condition('field_provider_subject_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function individualHasEmployment(string $provider_subj_no): bool {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'employment')
      ->condition('field_provider_subject_no', $provider_subj_no)
      ->accessCheck(TRUE)
      ->range(0, 1);

    $result = $query->execute();

    return !empty($result);
  }

  private function getInstallmentContract(string $contract_no): ?Node {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'installment_contract')
      ->condition('field_provider_contract_no', $contract_no)
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

  private function getNonInstallmentContract(string $contract_no): ?Node {
    $query = \Drupal::entityQuery('node')
      ->condition('type', 'noninstallment_contract')
      ->condition('field_provider_contract_no', $contract_no)
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

  public function createIndividual(array $row, int $row_number, array $map, array &$errors): void {
    $provider_subj_no = trim((string) ($row[$map['provider subject no']] ?? ''));
    $individual_node = $this->getIndividual($provider_subj_no);
    $individual_title = "[$provider_subj_no] " . trim((string) ($row[$map['first name']] ?? '')) . " " . trim((string) ($row[$map['last name']] ?? ''));
    if (is_null($individual_node)) {
      $individual_node = NodeHelper::createNodeFromMap(
        'individual', FieldMaps::INDIVIDUAL_FIELD_MAP, $row, $row_number, $map, $individual_title, $errors
      );
    }
    else {
      $individual_node = NodeHelper::updateNodeFromMap(
        $individual_node, FieldMaps::INDIVIDUAL_FIELD_MAP, $row, $row_number, $map, $errors, title: $individual_title
      );
    }

    if (!$this->individualHasFamily($provider_subj_no)) {
      $family_fields = [
        trim((string) ($row[$map['spouse first name']] ?? '')),
        trim((string) ($row[$map['spouse last name']] ?? '')),
        trim((string) ($row[$map['spouse middle name']] ?? '')),
        trim((string) ($row[$map['mother maiden full name'] ?? $map['mothers maiden full name']] ?? '')),
        trim((string) ($row[$map['father first name']] ?? '')),
        trim((string) ($row[$map['father last name']] ?? '')),
        trim((string) ($row[$map['father middle name']] ?? '')),
        trim((string) ($row[$map['father suffix']] ?? '')),
      ];

      $isFamilyEmpty = empty(array_filter($family_fields));
      if (!$isFamilyEmpty) {
        $family_title = "[$provider_subj_no] Family";
        $family_node = NodeHelper::createNodeFromMap(
          'family', FieldMaps::FAMILY_FIELD_MAP, $row, $row_number, $map, $family_title, $errors
        );
      }
    }

    if (!$this->individualHasAddress($provider_subj_no)) {
      $address_title = "[$provider_subj_no] Address";
      $address_node = NodeHelper::createNodeFromMap(
        'address', FieldMaps::ADDRESS_FIELD_MAP, $row, $row_number, $map, $address_title, $errors
      );
    }
    if (!$this->individualHasIdentification($provider_subj_no)) {
      $identification_title = "[$provider_subj_no] Identification";
      $identification_node = NodeHelper::createNodeFromMap(
        'identification', FieldMaps::IDENTIFICATION_FIELD_MAP, $row, $row_number, $map, $identification_title, $errors
      );
    }
    if (!$this->individualHasContact($provider_subj_no)) {
      $contact_title = "[$provider_subj_no] Contact";
      $contact_node = NodeHelper::createNodeFromMap(
        'contact', FieldMaps::CONTACT_FIELD_MAP, $row, $row_number, $map, $contact_title, $errors
      );
    }
    if (!$this->individualHasEmployment($provider_subj_no)) {
      $employment_title = "[$provider_subj_no] Employment";
      $employment_node = NodeHelper::createNodeFromMap(
        'employment', FieldMaps::EMPLOYMENT_FIELD_MAP, $row, $row_number, $map, $employment_title, $errors
      );
    }
  }

  public function createInstallment(Node $header_node, array $row, int $row_number, array $map, array &$errors): void {
    $provider_subj_no = trim((string) ($row[$map['provider subject no']] ?? ''));
    $contract_no      = trim((string) ($row[$map['provider contract no']] ?? ''));

    $installment_node = $this->getInstallmentContract($contract_no);

    if (is_null($installment_node)) {
      $installment_title = "[$provider_subj_no] Installment Contract";
      $installment_node = NodeHelper::createNodeFromMap(
        'installment_contract', FieldMaps::INSTALLMENT_FIELD_MAP, $row, $row_number, $map, $installment_title, $errors, $header_node
      );
    }
    else {
      $installment_node = NodeHelper::updateNodeFromMap(
        $installment_node, FieldMaps::INSTALLMENT_FIELD_MAP, $row, $row_number, $map, $errors, $header_node
      );
    }
  }

  public function createCompany(array $row, int $row_number, array $map, array &$errors): void {
    $provider_subj_no = trim((string) ($row[$map['provider subject no']] ?? ''));
    $company_node = $this->getCompany($provider_subj_no);
    $company_title = "[$provider_subj_no] Company";
    if (is_null($company_node)) {
      $company_node = NodeHelper::createNodeFromMap(
        'company', FieldMaps::COMPANY_FIELD_MAP, $row, $row_number, $map, $company_title, $errors
      );
    }
    else {
      $company_node = NodeHelper::updateNodeFromMap(
        $company_node, FieldMaps::COMPANY_FIELD_MAP, $row, $row_number, $map, $errors
      );
    }

    if (!$this->individualHasAddress($provider_subj_no)) {
      $address_title = "[$provider_subj_no] Address";
      $address_node = NodeHelper::createNodeFromMap(
        'address', FieldMaps::ADDRESS_FIELD_MAP, $row, $row_number, $map, $address_title, $errors
      );
    }
    if (!$this->individualHasIdentification($provider_subj_no)) {
      $identification_title = "[$provider_subj_no] Identification";
      $identification_node = NodeHelper::createNodeFromMap(
        'identification', FieldMaps::IDENTIFICATION_FIELD_MAP, $row, $row_number, $map, $identification_title, $errors
      );
    }
    if (!$this->individualHasContact($provider_subj_no)) {
      $contact_title = "[$provider_subj_no] Contact";
      $contact_node = NodeHelper::createNodeFromMap(
        'contact', FieldMaps::CONTACT_FIELD_MAP, $row, $row_number, $map, $contact_title, $errors
      );
    }
  }

  public function createNonInstallment(Node $header_node, array $row, int $row_number, array $map, array &$errors): void {
    $provider_subj_no = trim((string) ($row[$map['provider subject no']] ?? ''));
    $contract_no      = trim((string) ($row[$map['provider contract no']] ?? ''));

    $noninstallment_node = $this->getNonInstallmentContract($contract_no);

    if (is_null($noninstallment_node)) {
      $noninstallment_title = "[$provider_subj_no] Non-Installment Contract";
      $noninstallment_node = NodeHelper::createNodeFromMap(
        'noninstallment_contract', FieldMaps::NONINSTALLMENT_FIELD_MAP, $row, $row_number, $map, $noninstallment_title, $errors, $header_node
      );
    }
    else {
      $noninstallment_node = NodeHelper::updateNodeFromMap(
        $noninstallment_node, FieldMaps::NONINSTALLMENT_FIELD_MAP, $row, $row_number, $map, $errors, $header_node
      );
    }
  }
}