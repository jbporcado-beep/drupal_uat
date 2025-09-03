<?php 
namespace Drupal\cooperative\Controller;
use Drupal\Core\Controller\ControllerBase;

class ReportsController extends ControllerBase {

    public function list(): array {
        return [
            '#title' => $this->t('Reports'),
            '#plain_text' => $this->t('Reports list goes here'),
        ];
    }

    public function view(string $id): array {
        return [
            '#title' => $this->t('Report @id', ['@id' => $id]),
            '#plain_text' => $this->t('Details for report @id', ['@id' => $id]),
        ];
    }
}