<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\file\FileInterface;
use Drupal\user\Entity\User;

class FileHistoryRepository {

    public function save(FileInterface $file, Node $header): Node {
        $current_user = \Drupal::currentUser();
        $user_id = $current_user->id();
        $user = User::load($user_id);
        $username = $user->get('field_full_name')->value ?? $user->getAccountName();
        $branch_code = $header->get('field_branch_code')->value ?? '';
        $provider_code = $header->get('field_provider_code')->value ?? '';

        $branch = $this->findBranch($branch_code);
        $coop = $this->findCooperative($provider_code);

        date_default_timezone_set('Asia/Manila');
        $current_date = date('F j, Y');

        $filename = $file->getFilename();
        $filename = $this->truncateStringIfMoreThanMaxLen($filename, 255);

        try {
            $file->setPermanent();
            $file->save();

            $values = [
                'type' => 'file_upload_history',
                'title' => "[" .$file->getFilename()."] File Upload",
                'status' => 1,
                'uid' => $user_id,
                'field_header' => $header,
                'field_file' => $file->id(),
                'field_date_uploaded' => $current_date,
                'field_uploaded_by' => $user_id,
                'field_branch' => $branch,
                'field_cooperative' => $coop,
                'field_uploader_name' => $username,
                'field_file_name' => $filename,
            ];
            $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
            $node->save();
            return $node;
        } catch (\Exception $e) {
            \Drupal::logger('cooperative')->error('Failed to create file history node: @message', ['@message' => $e->getMessage()]);
            $this->messenger()->setError($this->t('An error occurred while creating the upload history record.'));
        }
    }

    public function findByHeader(Node $header): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'file_upload_history')
        ->condition('field_header', $header)
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

    private function findBranch(?string $branch_code): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'branch')
        ->condition('field_branch_code', $branch_code)
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

    private function findCooperative(?string $provider_code): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'cooperative')
        ->condition('field_cic_provider_code', $provider_code)
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

    private function truncateStringIfMoreThanMaxLen(string $string, int $maxLength): string {
    if (mb_strlen($string) > $maxLength) {
        return mb_substr($string, 0, $maxLength);
    }
    return $string;
}

}

?>