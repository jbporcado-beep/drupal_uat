<?php
namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\admin\Form\DashboardForm;

class HomeController extends ControllerBase {
  public function view() {
    return $this->formBuilder()->getForm(DashboardForm::class);
  }
}