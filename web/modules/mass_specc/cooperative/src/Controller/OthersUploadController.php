<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cooperative\Form\OthersUploadForm;

class OthersUploadController extends ControllerBase {
  public function form() {
    return $this->formBuilder()->getForm(OthersUploadForm::class);
  }
}