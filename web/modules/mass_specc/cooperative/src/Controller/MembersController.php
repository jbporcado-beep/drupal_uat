<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;

class MembersController extends ControllerBase {
  public function list() {
    return [
      '#title' => $this->t('Members'),
      '#plain_text' => $this->t('Members here'),
    ];
  }

  public function view(string $id) {
    return [
      '#title' => $this->t('Member @id', ['@id' => $id]),
      '#plain_text' => $this->t('Details for member @id', ['@id' => $id]),
    ];
  }
}