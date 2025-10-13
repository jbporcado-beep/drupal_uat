<?php

namespace Drupal\admin\Component;

use Drupal\Core\Url;
use Drupal\user\Entity\Role;

class RolesTable
{

    public static function render($is_active = TRUE)
    {
        $rows = [];

        $all_permissions = array_keys(\Drupal::service('user.permissions')->getPermissions());
        $max_permissions = count($all_permissions);
        $roles = Role::loadMultiple();

        foreach ($roles as $role) {
            if (in_array($role->id(), ['anonymous', 'authenticated'])) {
                continue;
            }

            $permissions = $role->getPermissions();
            $privilege_count = ($role->id() === 'administrator')
                ? $max_permissions
                : count($permissions);

            $info_url = Url::fromRoute('users.roles.view', [
                'role' => $role->id(),
            ]);

            $action_link = [
                '#type' => 'link',
                '#title' => [
                    '#markup' => '<i class="fas fa-info-circle"></i>',
                ],
                '#url' => $info_url,
                '#attributes' => [
                    'class' => ['action-edit-btn', 'use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => json_encode(['width' => 700]),
                    'title' => t('View role details'),
                ],
            ];

            $action_markup = \Drupal::service('renderer')->renderPlain($action_link);

            if (!$is_active) {
                $action_markup = '<span class="action-edit-btn disabled" style="pointer-events: none; opacity: 0.6;"><i class="fas fa-info-circle"></i></span>';
            }

            $rows[] = [
                'data' => [
                    ['data' => htmlspecialchars($role->label())],
                    ['data' => htmlspecialchars($privilege_count . ' privileges')],
                    ['data' => ['#markup' => $action_markup]],
                ],
            ];
        }

        return [
            '#type' => 'table',
            '#header' => [
                t('Role'),
                t('Privileges'),
                t('Action'),
            ],
            '#rows' => $rows,
            '#empty' => t('No roles found.'),
            '#attributes' => [
                'id' => 'roles-table',
                'class' => [
                    'table',
                    'table-bordered',
                    'table-hover',
                    !$is_active ? 'table-disabled' : '',
                ],
            ],
            '#cache' => ['max-age' => 0],
            '#striping' => FALSE,
        ];
    }

    /**
     * Modal callback for viewing role permissions.
     */
    public static function viewModal($role)
    {
        $role_entity = Role::load($role);
        if (!$role_entity) {
            return ['#markup' => t('Role not found.')];
        }

        $permission_service = \Drupal::service('user.permissions');
        $all_permissions = $permission_service->getPermissions();

        if (in_array($role_entity->id(), ['administrator', 'superadmin'])) {
            $permissions = array_keys($all_permissions);
        } else {
            $permissions = $role_entity->getPermissions();
        }

        $items = [];
        foreach ($permissions as $perm_id) {
            if (!isset($all_permissions[$perm_id])) {
                continue;
            }

            $perm_title = $all_permissions[$perm_id]['title'] ?? $perm_id;

            $perm_title = preg_replace('/<em class="placeholder">(.*?)<\/em>/', '<em>$1</em>', $perm_title);
            $perm_title = \Drupal\Component\Utility\Html::decodeEntities(strip_tags($perm_title));

            $items[] = [
                '#markup' => '<div class="permission-item">' . htmlspecialchars($perm_title) . '</div>',
            ];
        }

        $content = [
            '#type' => 'container',
            '#attributes' => ['style' => 'padding: 1rem;'],
            'header' => [
                '#markup' => '<h2 style="margin-bottom: 1rem; font-weight: 600;">' . htmlspecialchars($role_entity->label()) . '</h2>',
            ],
            'permissions' => [
                '#type' => 'container',
                '#attributes' => [
                    'style' => '
                    max-height: 400px;
                    overflow-y: auto;
                    padding: 0.75rem 1rem;
                    border-radius: 0.5rem;
                    background-color: #fafafa;
                ',
                ],
                'items' => [
                    '#theme' => 'item_list',
                    '#items' => $items,
                    '#attributes' => [
                        'style' => 'padding-left: 0.5rem; margin: 0;',
                    ],
                ],
            ],
            '#attached' => [
                'html_head' => [
                    [
                        [
                            '#tag' => 'style',
                            '#value' => '
                            /* 🧠 Improve permission item readability */
                            .permission-item {
                                padding: 0.4rem 0.2rem;
                                border-bottom: 1px solid #eee;
                                font-size: 0.95rem;
                                color: #333;
                            }

                            .permission-item:last-child {
                                border-bottom: none;
                            }
                        ',
                        ],
                        'role_modal_styles',
                    ],
                ],
            ],
        ];

        return $content;
    }


}
