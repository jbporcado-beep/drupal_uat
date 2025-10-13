<?php
namespace Drupal\admin\Component;

use Drupal\Core\Url;

class CoopBranchesTable
{
    public static function render($coop_id, $branches = [], $is_active = TRUE)
    {
        $rows = [];

        foreach ($branches as $branch) {
            $is_staged = !empty($branch['is_staged']);
            $row_class = [];
            $style = '';

            if ($is_staged) {
                $row_class[] = 'staged-branch';
                $style = 'font-style: italic;';
            }

            $branch_key = $branch['branch_id'] ?: ($branch['uuid'] ?? NULL);

            $edit_url = Url::fromRoute('cooperative.branches.edit', [
                'id' => $coop_id,
                'branch_key' => $branch_key,
            ]);

            $action_link = [
                '#type' => 'link',
                '#title' => [
                    '#markup' => '<i class="fas fa-pencil-alt"></i>',
                ],
                '#url' => $edit_url,
                '#attributes' => [
                    'class' => ['action-edit-btn', 'use-ajax'],
                    'data-dialog-type' => 'modal',
                    'data-dialog-options' => json_encode(['width' => 800]),
                    'data-branch-key' => $branch_key,
                ],
            ];

            $action_markup = \Drupal::service('renderer')->renderPlain($action_link);

            if ($is_staged) {
                $action_markup .= ' <span class="badge bg-warning text-dark ms-2">Staged</span>';
            }

            if (!$is_active) {
                $row_class[] = 'disabled-row';
                $action_markup = '<span class="action-edit-btn disabled" style="pointer-events: none; opacity: 0.6;"><i class="fas fa-pencil-alt"></i></span>';
            }

            $rows[] = [
                'data' => [
                    ['data' => htmlspecialchars($branch['branch_code']), 'style' => $style],
                    ['data' => htmlspecialchars($branch['branch_name']), 'style' => $style],
                    ['data' => htmlspecialchars($branch['email']), 'style' => $style],
                    ['data' => htmlspecialchars($branch['contact_person']), 'style' => $style],
                    ['data' => ['#markup' => $action_markup]],
                ],
                'class' => $row_class,
            ];
        }

        return [
            '#type' => 'table',
            '#header' => [
                t('Branch Code'),
                t('Branch Name'),
                t('Email'),
                t('Contact Person'),
                t('Action'),
            ],
            '#rows' => $rows,
            '#empty' => t('No results found.'),
            '#attributes' => [
                'id' => 'branches-table',
                'class' => ['table', 'table-bordered', 'table-hover', !$is_active ? 'table-disabled' : ''],
            ],
            '#cache' => ['max-age' => 0],
            '#striping' => FALSE,
        ];
    }

}
