<?php 
namespace Drupal\admin\Controller;
use Drupal\Core\Controller\ControllerBase;

class CicController extends ControllerBase {
public function viewCicFiles(): array {
        return [
            '#title' => $this->t('View CIC Files'),
            '#plain_text' => $this->t('CIC files list "history" goes here, only the admin can access it'),
        ];
    }

    public function generateCic(): array {
        return [
            '#title' => $this->t('Generate CIC'),
            '#plain_text' => $this->t('CIC generation stuff goes here'),
        ];
    }
}