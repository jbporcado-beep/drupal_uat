<?php

namespace Drupal\admin\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\user\Entity\Role;

class RolesTableController extends ControllerBase
{
    public function content()
    {
        $header = ['Role', 'Privileges'];
        $rows = [];

        $all_permissions = array_keys(\Drupal::service('user.permissions')->getPermissions());
        $max_permissions = count($all_permissions);

        $roles = Role::loadMultiple();
        foreach ($roles as $role) {

            if (in_array($role->id(), ['anonymous', 'authenticated'])) {
                continue;
            }

            $permissions = $role->getPermissions();
            if ($role->id() === 'administrator') {
                $privileges = $max_permissions . " privileges";
            } else {
                $privileges = count($permissions) . " privileges";
            }
            $rows[] = [
                $role->label(),
                $privileges,
            ];
        }

        return [
            '#type' => 'table',
            '#header' => $header,
            '#rows' => $rows,
            '#empty' => $this->t('No roles found.'),
            '#attributes' => [
                'class' => ['table', 'table-bordered'],
            ],
        ];
    }
}
