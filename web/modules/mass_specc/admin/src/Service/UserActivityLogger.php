<?php

namespace Drupal\admin\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\Component\Serialization\Json;
use Drupal\Component\Datetime\TimeInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * User activity logger service.
 */
class UserActivityLogger
{
  protected Connection $database;
  protected AccountProxyInterface $currentUser;
  protected TimeInterface $time;
  protected ?DateFormatterInterface $dateFormatter;
  protected ?EntityTypeManagerInterface $entityTypeManager;

  /**
   * @var \Drupal\diff\DiffEntityComparison|null
   */
  protected $diffEntityComparison;

  public function __construct(
    Connection $database,
    AccountProxyInterface $currentUser,
    TimeInterface $time,
    ?DateFormatterInterface $dateFormatter = NULL,
    ?EntityTypeManagerInterface $entity_type_manager = NULL,
    $diff_builder = NULL
  ) {
    $this->database = $database;
    $this->currentUser = $currentUser;
    $this->time = $time;

    $this->dateFormatter = $dateFormatter ?: (\Drupal::hasService('date.formatter') ? \Drupal::service('date.formatter') : NULL);
    $this->entityTypeManager = $entity_type_manager ?: (\Drupal::hasService('entity_type.manager') ? \Drupal::service('entity_type.manager') : NULL);

    $this->diffEntityComparison = $diff_builder ?? (\Drupal::hasService('diff.entity_comparison') ? \Drupal::service('diff.entity_comparison') : NULL);
  }

  public function log(string $action, ?string $entity_type = NULL, $entity_id = NULL, array $data = [], ?string $summary = NULL, $actor = NULL): void
  {
    $created = $this->time->getCurrentTime();

    $performed_by_uid = 0;
    $performed_by_name = '';

    if ($actor instanceof AccountInterface) {
      $performed_by_uid = $actor->id();
      $performed_by_name = $actor->getDisplayName();
    } elseif (is_string($actor) && $actor !== '') {
      if (strpos($actor, '@') !== FALSE) {
        $uid = $this->loadUidByEmail($actor);
        if ($uid) {
          $performed_by_uid = $uid;
          $performed_by_name = $this->loadDisplayNameByUid($uid);
        } else {
          $performed_by_name = $actor;
        }
      } else {
        $performed_by_name = $actor;
      }
    } elseif (!empty($data['performed_by_email'])) {
      $uid = $this->loadUidByEmail($data['performed_by_email']);
      if ($uid) {
        $performed_by_uid = $uid;
        $performed_by_name = $this->loadDisplayNameByUid($uid);
      } else {
        $performed_by_name = $data['performed_by_email'];
      }
    } elseif (!empty($data['performed_by_name'])) {
      $performed_by_name = $data['performed_by_name'];
    } else {
      if ($this->currentUser && $this->currentUser->isAuthenticated()) {
        $performed_by_uid = (int) $this->currentUser->id();
        $performed_by_name =
          $this->currentUser->getDisplayName()
          ?: $this->currentUser->getAccountName()
          ?: ('User #' . $performed_by_uid);
      } else {
        $performed_by_name = 'System';
      }
    }

    if ($summary === NULL) {
      $summary = $this->buildSummary($action, $data, $created, $performed_by_name);
    }

    $subject_user_id = $data['user_id'] ?? 0;

    $this->database->insert('user_activity_log')
      ->fields([
        'user_id' => $subject_user_id,
        'performed_by' => (int) $performed_by_uid,
        'performed_by_name' => $performed_by_name,
        'action' => $action,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'summary' => $summary,
        'data' => Json::encode($data),
        'created' => $created,
      ])
      ->execute();
  }



  /**
   * Try to load a user id by email. Returns uid or 0.
   */
  protected function loadUidByEmail(string $email): int
  {
    if (empty($email) || $this->entityTypeManager === NULL) {
      return 0;
    }
    try {
      $storage = $this->entityTypeManager->getStorage('user');
      $users = $storage->loadByProperties(['mail' => $email]);
      if (!empty($users)) {
        $user = reset($users);
        return method_exists($user, 'id') ? $user->id() : 0;
      }
    } catch (\Throwable $e) {
    }
    return 0;
  }

  /**
   * Load display name for a uid. Returns empty string if not found.
   */
  protected function loadDisplayNameByUid(int $uid): string
  {
    if ($uid <= 0 || $this->entityTypeManager === NULL) {
      return '';
    }
    try {
      $storage = $this->entityTypeManager->getStorage('user');
      $user = $storage->load($uid);
      if ($user && method_exists($user, 'getDisplayName')) {
        return $user->getDisplayName();
      }
      if ($user && method_exists($user, 'label')) {
        return $user->label();
      }
    } catch (\Throwable $e) {
    }
    return '';
  }

  /**
   * Compute changed fields between an original and updated entity.
   *
   * @param \Drupal\Core\Entity\EntityInterface $original
   * @param \Drupal\Core\Entity\EntityInterface $updated
   * @param array|null $fields_to_check
   *
   * @return array
   */
  public function getChangedFields(EntityInterface $original, EntityInterface $updated, array $fields_to_check = NULL): array
  {
    if (!$original instanceof EntityInterface || !$updated instanceof EntityInterface) {
      return [];
    }
    $entity_type = $updated->getEntityTypeId();

    if ($fields_to_check === NULL) {
      $defaults = [
        'node' => ['title', 'body', 'field_coop_code', 'field_coop_name'],
        'user' => ['mail', 'roles', 'field_bio'],
      ];
      $fields_to_check = $defaults[$entity_type] ?? [];
    }

    $changed = [];

    if ($this->diffEntityComparison !== NULL) {
      try {
        if (method_exists($this->diffEntityComparison, 'compareRevisions')) {
          $diff_array = $this->diffEntityComparison->compareRevisions($original, $updated);
          if (is_array($diff_array)) {
            foreach ($fields_to_check as $field_name) {
              if (!empty($diff_array[$field_name])) {
                $left_html = $diff_array[$field_name]['left'] ?? '';
                $right_html = $diff_array[$field_name]['right'] ?? '';
                $left_text = trim(strip_tags((string) $left_html));
                $right_text = trim(strip_tags((string) $right_html));
                if ($left_text !== $right_text) {
                  $changed[$field_name] = ['from' => $left_text, 'to' => $right_text];
                }
              }
            }
            if (!empty($changed)) {
              return $changed;
            }
          }
        }
      } catch (\Throwable $e) {

      }
    }

    foreach ($fields_to_check as $field_name) {
      try {
        $old_raw = $original->hasField($field_name) ? $original->get($field_name)->getValue() : NULL;
        $new_raw = $updated->hasField($field_name) ? $updated->get($field_name)->getValue() : NULL;
      } catch (\Throwable $e) {
        continue;
      }

      if ($entity_type === 'user' && $field_name === 'roles') {
        $old_roles = method_exists($original, 'getRoles') ? $original->getRoles() : [];
        $new_roles = method_exists($updated, 'getRoles') ? $updated->getRoles() : [];
        sort($old_roles);
        sort($new_roles);
        if ($old_roles !== $new_roles) {
          $changed[$field_name] = ['from' => implode(', ', $old_roles), 'to' => implode(', ', $new_roles)];
        }
        continue;
      }

      $old_text = $this->normalizeFieldValue($old_raw);
      $new_text = $this->normalizeFieldValue($new_raw);
      if ($old_text !== $new_text) {
        $changed[$field_name] = ['from' => $old_text, 'to' => $new_text];
      }
    }

    return $changed;
  }

  /**
   * Normalize a field value (scalar/array) into a readable string.
   */
  protected function normalizeFieldValue($raw): string
  {
    if (is_null($raw)) {
      return '';
    }
    if (is_scalar($raw)) {
      return (string) $raw;
    }
    if (is_array($raw)) {
      $items = [];
      foreach ($raw as $item) {
        if (is_array($item) && isset($item['value'])) {
          $items[] = (string) $item['value'];
        } elseif (is_array($item) && isset($item['target_id'])) {
          $items[] = (string) $item['target_id'];
        } else {
          $items[] = Json::encode($item);
        }
      }
      return implode(', ', $items);
    }
    return Json::encode($raw);
  }

  /**
   * Build a human-readable summary for the activity log.
   *
   * Accepts optional $performed_by_name to avoid re-resolving the actor.
   */
  protected function buildSummary(string $action, array $data, $created, string $performed_by_name = ''): string
  {
    $performed_by_name = $data['performed_by_name'] ?? $performed_by_name ?? ($this->currentUser ? $this->currentUser->getDisplayName() : '');

    $formatted = $this->dateFormatter ? $this->dateFormatter->format($created, 'custom', 'F j, Y; g:i A') : date('F j, Y; g:i A', $created);

    if (!empty($data['file_name']) && stripos($action, 'download') !== FALSE) {
      return "{$action}\nFile name: {$data['file_name']}\nDate and Time: {$formatted}\nby User: {$performed_by_name}";
    }

    if (!empty($data['changed_fields']) && !empty($data['user_name'])) {
      $lines = [];
      $lines[] = $action;
      $lines[] = 'User name: ' . $data['user_name'];
      foreach ($data['changed_fields'] as $field => $pair) {
        $label = $data['field_labels'][$field] ?? ucfirst($field);
        if (isset($pair['from'])) {
          $lines[] = "{$label} From: {$pair['from']}";
        }
        if (isset($pair['to'])) {
          $lines[] = "{$label} To: {$pair['to']}";
        }
      }
      $lines[] = "Date and Time: {$formatted}";
      $lines[] = "by User: {$performed_by_name}";
      return implode("\n", $lines);
    }

    return "{$action}\nDate and Time: {$formatted}\nby User: {$performed_by_name}";
  }
}
