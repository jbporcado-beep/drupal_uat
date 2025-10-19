<?php
namespace Drupal\cooperative\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\cooperative\Form\DashboardForm;
use Drupal\node\Entity\Node;
use Drupal\user\Entity\User;

class HomeController extends ControllerBase {
  public function view() {
    return $this->formBuilder()->getForm(DashboardForm::class);
  }

  public function getTitle(): string {
    $user = \Drupal::currentUser();
    $user_nid = $user->id();
    $user_entity = User::load($user_nid);
    $coop_nid = $user_entity->get('field_cooperative')->target_id;
    $coop_entity = Node::load($coop_nid);
    $coop_name = $coop_entity->get('field_coop_name')->value;
    return $coop_name;
  }
}