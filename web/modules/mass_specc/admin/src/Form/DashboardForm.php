<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

class DashboardForm extends FormBase {

    public function getFormId() {
        return 'admin_dashboard_form';
    }

    private function getTotalSubjectCount(): int {
        $query_indiv = \Drupal::entityQuery('node')
            ->condition('type', 'individual')
            ->accessCheck(TRUE);
        $indiv_count = $query_indiv->count()->execute();

        return $indiv_count;
    }

    private function getTotalBranchByCoop(int $coop_nid): int {
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'branch')
            ->condition('field_branch_coop', $coop_nid)
            ->accessCheck(TRUE);
        $count = $query->count()->execute();

        return $count;
    }

    private function getTotalContractCount(): int {
        $query_ci = \Drupal::entityQuery('node')
            ->condition('type', 'installment_contract')
            ->accessCheck(TRUE);
        $ci_count = $query_ci->count()->execute();

        $query_cn = \Drupal::entityQuery('node')
            ->condition('type', 'noninstallment_contract')
            ->accessCheck(TRUE);
        $cn_count = $query_cn->count()->execute();

        $total = $ci_count + $cn_count;
        return $total;
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/dashboard';
        $form['#attached']['library'][] = 'admin/dashboard_autosubmit';

        $form['#method'] = 'POST';

        $total_subjects = $this->getTotalSubjectCount();
        $total_contracts = $this->getTotalContractCount();
        $total = $total_subjects + $total_contracts;

        $form['accounts'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['account-container']],
        ];
        $form['accounts']['stats'] = [
            '#type' => 'markup',
            '#markup' => "
                <h3 class='stat-header'>Accounts</h3>
                <div class='account-stat'>
                    <p class='left-p'>Individual Accounts</p>
                    <p class='right-p'>{$total_subjects}</p>
                </div>
                <div class='account-stat'>
                    <p class='left-p'>Contract Accounts</p>
                    <p class='right-p'>{$total_contracts}</p>
                </div>
                <div class='account-stat'>
                    <p class='left-p total'>TOTAL</p>
                    <p class='right-p'>{$total}</p>
                </div>
            ",
        ];


        $search = $form_state->getValue('search', '');

        $request = \Drupal::request();
        if ($request->query->get('search') !== null && !$form_state->getUserInput()) {
            $search = $request->query->get('search');
        }
        
        $form['search_submit'] = [
            '#type' => 'submit',
            '#value' => 'Search',
            '#attributes' => ['style' => 'display:none;'],
        ];

        $form['search'] = [
            '#type' => 'textfield',
            '#default_value' => $search,
            '#size' => 30,
            '#attributes' => [
                'placeholder' => 'Search',
                'class' => ['searchbar'],
            ],
        ];

        $form['table'] = $this->buildDashboardTable($search);
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $request = \Drupal::request();

        $request->query->remove('page');

        $form_state->setRebuild(TRUE);
    }

    private function buildDashboardTable($search = '') {
        $limit = 10;
        $rows = [];

        $request = \Drupal::request();
        $query = $request->query->all();

        if (strlen(trim($search)) === 0 && $request->query->has('search')) {
            $request->query->remove('search');
            unset($query['search']);
        } else {
            $query['search'] = $search;
        }

        $current_month_start = strtotime(date('Y-m-01 00:00:00'));
        $current_month_end = strtotime(date('Y-m-t 23:59:59'));

        $all_coops = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties(['type' => 'cooperative']);

        foreach ($all_coops as $coop) {
            $coop_id = $coop->id();
            $coop_name = $coop->get('field_coop_name')->value;

            $query = \Drupal::entityQuery('node')
                ->condition('type', 'file_upload_history')
                ->condition('field_cooperative', $coop_id)
                ->condition('created', [$current_month_start, $current_month_end], 'BETWEEN')
                ->sort('created', 'DESC')
                ->accessCheck(FALSE);

            $nids = $query->execute();
            $uploads = !empty($nids) ? Node::loadMultiple($nids) : [];

            $latest_per_branch = [];

            foreach ($uploads as $upload) {
                $branch_id = $upload->get('field_branch')->target_id ?? 'default';

                if (isset($latest_per_branch[$branch_id])) {
                    continue;
                }

                if ($upload->get('field_status')->value === 'Approved') {
                    $latest_per_branch[$branch_id] = $upload;
                }
            }

            $approved_uploads = count($latest_per_branch);
            $total_branches = $this->getTotalBranchByCoop($coop_id);

            $branch_column = "{$approved_uploads}/{$total_branches} approved";
            $status = $approved_uploads === $total_branches ? 'Complete' : 'Partial';

            $rows[] = [
                'coop_name' => $coop_name,
                'branch_column' => $branch_column,
                'status' => $status,
            ];
        }

        if (!empty($search)) {
            $search_lower = strtolower($search);
            $rows = array_filter($rows, function ($row) use ($search_lower) {
            return
                str_contains(strtolower($row['coop_name']), $search_lower) ||
                str_contains(strtolower($row['branch_column']), $search_lower) ||
                str_contains(strtolower($row['status']), $search_lower);
            });
        }

        $total = count($rows);
        $pager_manager = \Drupal::service('pager.manager');
        $pager = $pager_manager->createPager($total, $limit);

        $current_page = $pager->getCurrentPage();
        $offset = $current_page * $limit;
        $paged_rows = array_slice($rows, $offset, $limit);

        $header = [
            ['data' => $this->t('Cooperative')],
            ['data' => $this->t('Branches')],
            ['data' => $this->t('Status')],
        ];

        $build['dashboard_table'] = [
            '#type' => 'table',
            '#header' => $header,
            '#attributes' => [
                'class' => ['coop-summary-table'],
            ],
            '#rows' => array_map(function ($row) {
            return [
                'data' => [
                ['data' => $row['coop_name']],
                ['data' => $row['branch_column']],
                ['data' => $row['status']],
                ],
            ];
            }, $paged_rows),
            '#empty' => $this->t('No cooperatives found.'),
        ];

        $pager_parameters = [];
        if (!empty($search)) {
            $pager_parameters['search'] = $search;
        }

        $build['pager'] = [
            '#type' => 'pager',
            '#theme' => 'views_mini_pager',
            '#parameters' => $pager_parameters,
        ];

        return $build;
    }
}