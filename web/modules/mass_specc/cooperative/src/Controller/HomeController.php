<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;

class HomeController extends ControllerBase {
  public function view() {
    return [
      '#plain_text' => $this->t('Home Page'),
    ];
  }
}