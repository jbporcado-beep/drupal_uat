<?php

namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\node\NodeInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class FileHistoryController extends ControllerBase {

  public function approve(NodeInterface $node) {
    return $this->updateStatus($node, 'Approved');
  }

  public function reject(NodeInterface $node) {
    return $this->updateStatus($node, 'Rejected');
  }

  /**
   * @return \Symfony\Component\HttpFoundation\JsonResponse
   * A JSON response.
   */
  private function updateStatus(NodeInterface $node, string $status) {
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
        return new JsonResponse(['status' => 'success', 'new_status' => $status]);
      }
      catch (\Exception $e) {
        $this->getLogger('cooperative')->error('Error updating file status: @error', ['@error' => $e->getMessage()]);
        return new JsonResponse(['status' => 'error', 'message' => $this->t('An error occurred during save.')], Response::HTTP_INTERNAL_SERVER_ERROR);
      }
    }
    return new JsonResponse(['status' => 'error', 'message' => $this->t('Field field_status not found.')], Response::HTTP_BAD_REQUEST);
  }

}