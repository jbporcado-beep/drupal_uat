<?php

namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;

class CooperativeController extends ControllerBase {

    public function addCooperative(): array {
        return $this->formBuilder()->getForm(\Drupal\admin\Form\CooperativeCreateForm::class);
    }
}
