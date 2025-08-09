<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cooperative\Form\UploadForm;

class UploadController extends ControllerBase {
  public function form() {
    return $this->formBuilder()->getForm(UploadForm::class);
  }
}