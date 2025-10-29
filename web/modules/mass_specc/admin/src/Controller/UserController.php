<?php
namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\User;
use Drupal\admin\Component\RolesTable;
/**
 * Returns responses for user creation page.
 */
class UserController extends ControllerBase
{

    public function createUser(): array
    {
        return $this->formBuilder()->getForm(\Drupal\admin\Form\UserForm::class);
    }

    public function viewAllUsers(): array
    {
        return [
            '#title' => $this->t('All Users'),
            '#plain_text' => $this->t('List of all users'),
        ];
    }
    public function viewUser(string $id): array
    {
        return [
            '#title' => $this->t('User @id', ['@id' => $id]),
            '#plain_text' => $this->t('Details for user @id', ['@id' => $id]),
        ];
    }

    public function editUser(string $id): array
    {
        $user = User::load($id);
        return $this->formBuilder()->getForm(\Drupal\admin\Form\UserForm::class, $user);
    }

    public function userRoles()
    {
        return [
            '#title' => $this->t('User Roles'),
            'roles_table' => RolesTable::render(),
        ];
    }
}