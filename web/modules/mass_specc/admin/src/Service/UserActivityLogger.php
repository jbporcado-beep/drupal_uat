<?php
namespace Drupal\admin\Service;

use Drupal\Core\Database\Connection;
use Drupal\Core\Session\AccountProxyInterface;

class UserActivityLogger
{

  protected $database;
  protected $currentUser;

  public function __construct(Connection $database, AccountProxyInterface $currentUser)
  {
    $this->database = $database;
    $this->currentUser = $currentUser;
  }

  public function log($action, $entity_type = NULL, $entity_id = NULL, array $data = [])
  {
    $this->database->insert('user_activity_log')
      ->fields([
        'user_id' => $this->currentUser->id(),
        'action' => $action,
        'entity_type' => $entity_type,
        'entity_id' => $entity_id,
        'data' => json_encode($data),
        'performed_by' => $this->currentUser->id(),
        'created' => \Drupal::time()->getCurrentTime(),
      ])
      ->execute();
  }
}

