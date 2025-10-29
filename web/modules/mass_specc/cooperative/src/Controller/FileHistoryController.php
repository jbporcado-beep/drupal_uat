<?php

namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Drupal\admin\Service\UserActivityLogger;
use Drupal\Core\Session\AccountProxyInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

class FileHistoryController extends ControllerBase
{

  protected $activityLogger;
  protected $currentUser;

  public function __construct(UserActivityLogger $activityLogger, AccountProxyInterface $currentUser)
  {
    $this->activityLogger = $activityLogger;
    $this->currentUser = $currentUser;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('admin.user_activity_logger'),
      $container->get('current_user')
    );
  }
  public function approve(NodeInterface $node)
  {
    return $this->updateStatus($node, 'Approved');
  }

  public function reject(NodeInterface $node)
  {
    return $this->updateStatus($node, 'Rejected');
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * A JSON response.
   */
  private function updateStatus(NodeInterface $node, string $status)
  {
    $account = $this->currentUser();
    if (!in_array('approver', $account->getRoles())) {
      return new JsonResponse(['status' => 'error', 'message' => $this->t('Access denied.')], Response::HTTP_FORBIDDEN);
    }

    if ($node->bundle() !== 'file_upload_history') {
      return new JsonResponse(['status' => 'error', 'message' => $this->t('Invalid content type.')], Response::HTTP_BAD_REQUEST);
    }

    if ($node->hasField('field_status')) {
      try {
        $node->set('field_status', $status);
        $node->save();

        $file_entity = $node->get('field_file')->entity ?? NULL;
        $file_name = $file_entity ? $file_entity->getFilename() : 'Unknown File';

        $coop_name = $node->get('field_cooperative')->entity->label() ?? 'Unknown Cooperative';
        $branch_name = $node->get('field_branch')->entity->label() ?? 'Unknown Branch';

        $action = $status . ' ' . $file_name . ' for ' . $coop_name . ' - ' . $branch_name;

        $data = [
          'changed_fields' => [],
        ];

        $this->activityLogger->log($action, 'node', $node->id(), $data, NULL, $this->currentUser);
        return new JsonResponse(['status' => 'success', 'new_status' => $status]);
      } catch (\Exception $e) {
        $this->getLogger('cooperative')->error('Error updating file status: @error', ['@error' => $e->getMessage()]);
        return new JsonResponse(['status' => 'error', 'message' => $this->t('An error occurred during save.')], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }
    return new JsonResponse(['status' => 'error', 'message' => $this->t('Field field_status not found.')], Response::HTTP_BAD_REQUEST);
  }

}