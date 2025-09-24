<?php

namespace Drupal\cooperative\Utility;

use Drupal\node\Entity\Node;

class NodeHelper {
  
  public static function createNodeFromMap(
    string $type, array $field_map, array $row, int $row_number, array $map, string $title, array &$errors, Node $header_node = null
    ): ?Node {
    $values = [
      'type' => $type,
      'title' => $title,
      'status' => 1,
    ];
    foreach ($field_map as $normalized_label => $field_machine_name) {
      $values[$field_machine_name] = trim((string) ($row[$map[$normalized_label] ?? ''] ?? ''));
    }
    $node = Node::create($values);
    if ($type === 'installment_contract' || $type === 'noninstallment_contract') {
      $node->set('field_header', $header_node);
    }
    $node->save();
    $violations = $node->validate();
    if ($violations->count() > 0) {
      foreach ($violations as $violation) {
        $field_name = $violation->getPropertyPath();
        $errors[] = "Row $row_number $field_name: " . $violation->getMessage();
      }
      return null;
    }
    return $node;
  }

  public static function updateNodeFromMap(
    Node $node, array $field_map, array $row, int $row_number, array $map, array &$errors, Node $header_node = null, string $title = ''
    ): ?Node {
    foreach ($field_map as $normalized_label => $field_machine_name) {
      $node->set($field_machine_name, trim((string) ($row[$map[$normalized_label] ?? ''] ?? '')));
    }
    if ($node->getType() === 'installment_contract' || $node->getType() === 'noninstallment_contract') {
      $node->set('field_header', $header_node);
    }
    if ($node->getType() === 'individual') {
      $node->setTitle($title);
    }
    $node->save();
    $violations = $node->validate();
    if ($violations->count() > 0) {
      foreach ($violations as $violation) {
        $field_name = $violation->getPropertyPath();
        $errors[] = "Row $row_number $field_name: " . $violation->getMessage();
      }
      return null;
    }
    return $node;
  }
}
