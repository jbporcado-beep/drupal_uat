<?php
namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;

class HomeController extends ControllerBase {
  public function view() {
    return [
      '#plain_text' => $this->t('Home page elements here'),
    ];
  }
}