<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cooperative\Form\DashboardForm;

class HomeController extends ControllerBase {
  public function view() {
    return $this->formBuilder()->getForm(DashboardForm::class);
  }
}