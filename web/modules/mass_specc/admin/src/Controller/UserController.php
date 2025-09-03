<?php
namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;
/**
 * Returns responses for user creation page.
 */
class UserController extends ControllerBase {

    public function createUser(): array {
        return $this->formBuilder()->getForm(\Drupal\admin\Form\UserCreateForm::class);
    }

    public function viewAllUsers(): array {
        return [
            '#title' => $this->t('All Users'),
            '#plain_text' => $this->t('List of all users'),
        ];
    }
    public function viewUser(string $id): array {
        return [
            '#title' => $this->t('User @id', ['@id' => $id]),
            '#plain_text' => $this->t('Details for user @id', ['@id' => $id]),
        ];
    }
}