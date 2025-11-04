<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;

use Drupal\charts_google\Plugin\chart\Library\Google;


class DashboardForm extends FormBase {

    public function getFormId() {
        return 'admin_dashboard_form';
    }

    private function getTotalSubjectCount(string $subject): int {
        $query = \Drupal::entityQuery('node')
            ->condition('type', $subject)
            ->accessCheck(TRUE);
        return $query->count()->execute();
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

    private function getAllActiveCoops(): array {
        $cooperative_options = [];
        $nids = \Drupal::entityQuery('node')
            ->condition('type', 'cooperative')
            ->condition('field_coop_status', 1)
            ->sort('title', 'ASC')
            ->accessCheck(FALSE)
            ->execute();
        if ($nids) {
            $nodes = Node::loadMultiple($nids);
            foreach ($nodes as $node) {
                $cooperative_options[$node->id()] = $node->getTitle();
            }
        }
        return $cooperative_options;
    }

    private function getBranches(): array {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $options = [];
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'branch')
        ->accessCheck(TRUE);
        $nids = $query->execute();
        if (!empty($nids)) {
            $nodes = $node_storage->loadMultiple($nids);
            foreach ($nodes as $node) {
                $options[$node->id()] = $node->getTitle();
            }
        }
        return $options;
    }

    private function getBranchOptionsByCoop(int $coop_nid): array {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $options = [];
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'branch')
        ->condition('field_branch_coop', $coop_nid)
        ->accessCheck(TRUE);
        $nids = $query->execute();
        if (!empty($nids)) {
            $branch_nodes = $node_storage->loadMultiple($nids);
            foreach ($branch_nodes as $branch) {
                $options[$branch->id()] = $branch->getTitle();
            }
        }
        return $options;
    }

    private function getCoopOptionsByBranch(int $branch_nid): array {
        $node_storage = \Drupal::entityTypeManager()->getStorage('node');
        $options = [];
        $branch = Node::load($branch_nid);
        if ($branch) {
            $coop = $branch->get('field_branch_coop')->entity;
            if ($coop) {
                $options[$coop->id()] = $coop->getTitle();
            }
        }
        return $options;
    }
    
    private function getCoopProviderCode(int $coop_nid): string {
        $node = Node::load($coop_nid);
        if ($node) {
            return $node->get('field_cic_provider_code')->value;
        }
        return '';
    }

    private function getSubjCountByCoop(int $coop_nid, string $subject): int {
        $provider_code = $this->getCoopProviderCode($coop_nid);

        if (empty($provider_code)) {
            return 0;
        }

        $query = \Drupal::entityQuery('node')
            ->condition('type', $subject)
            ->condition('field_provider_code', $provider_code)
            ->accessCheck(TRUE);
        return $query->count()->execute();
    }

    private function getSubjCountByBranch(string $branch_code, string $subject): int {
        if (empty($branch_code)) {
            return 0;
        }

        $query = \Drupal::entityQuery('node')
            ->condition('type', $subject)
            ->condition('field_branch_code', $branch_code)
            ->accessCheck(TRUE);
        return $query->count()->execute();
    }

    private function getContractCountByCoop(int $coop_nid): int {
        $provider_code = $this->getCoopProviderCode($coop_nid);

        if (empty($provider_code)) {
            return 0;
        }

        $query_ci = \Drupal::entityQuery('node')
            ->condition('type', 'installment_contract')
            ->condition('field_header.entity.field_provider_code', $provider_code)
            ->accessCheck(TRUE);
        $ci_count = $query_ci->count()->execute();

        $query_cn = \Drupal::entityQuery('node')
            ->condition('type', 'noninstallment_contract')
            ->condition('field_header.entity.field_provider_code', $provider_code)
            ->accessCheck(TRUE);
        $cn_count = $query_cn->count()->execute();

        $total = $ci_count + $cn_count;
        return $total;
    }

    private function getContractCountByBranch(string $branch_code): int {
        if (empty($branch_code)) {
            return 0;
        }

        $query_ci = \Drupal::entityQuery('node')
            ->condition('type', 'installment_contract')
            ->condition('field_header.entity.field_branch_code', $branch_code)
            ->accessCheck(TRUE);
        $ci_count = $query_ci->count()->execute();

        $query_cn = \Drupal::entityQuery('node')
            ->condition('type', 'noninstallment_contract')
            ->condition('field_header.entity.field_branch_code', $branch_code)
            ->accessCheck(TRUE);
        $cn_count = $query_cn->count()->execute();

        $total = $ci_count + $cn_count;
        return $total;
    }

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/dashboard';
        $form['#attached']['library'][] = 'admin/dashboard_autosubmit';

        $form['#method'] = 'GET';

        $coop_options = $this->getAllActiveCoops();
        $coop_count = count($coop_options);
        $branch_options = $this->getBranches();

        $form['stats-container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['stats-container']],
        ];

        $form['stats-container']['accts-container'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['accts-container'],
                'id' => 'accounts-wrapper',
            ],
        ];
        $form['stats-container']['accts-container']['top-side'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-topside']],
        ];
        $form['stats-container']['accts-container']['top-side']['header'] = [
            '#type' => 'markup',
            '#markup' => "<h4 class='stat-header'>Total Accounts</h4>",
        ];

        $form['stats-container']['total-coop-container'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['total-coop-container'],
                'id' => 'total-coop-wrapper',
            ],
        ];
        $form['stats-container']['total-coop-container']['top-side'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-topside']],
        ];
        $form['stats-container']['total-coop-container']['top-side']['header'] = [
            '#type' => 'markup',
            '#markup' => "<h4>Total Cooperatives: {$coop_count}</h4>",
        ];

        $request = \Drupal::request();
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');

        $stored_coop = $tempstore->get('coop_dropdown');
        $selected_coop = $form_state->getValue('coop_dropdown') ?? $request->query->get('coop_dropdown', $stored_coop ?? '');

        $stored_branch = $tempstore->get('branch_dropdown');
        $selected_branch = $form_state->getValue('branch_dropdown') ?? $request->query->get('branch_dropdown', $stored_branch ?? '');

        $branch_coop = [];
        if ($selected_coop) {
            $branch_options = $this->getBranchOptionsByCoop($selected_coop);
        }
        else if ($selected_branch) {
            $branch_coop = $this->getCoopOptionsByBranch($selected_branch);
        }

        $form['stats-container']['accts-container']['filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-filters']],
        ];

        $form['stats-container']['accts-container']['filters']['coop_dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('Cooperative'),
            '#options' => $selected_branch ? $branch_coop : $coop_options,
            '#default_value' => $selected_coop,
            '#ajax' => [
                'callback' => '::updateStatsCallback',
                'wrapper' => 'accounts-wrapper',
                'event' => 'change',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['coop-select']],
        ];
        $form['stats-container']['accts-container']['filters']['branch_dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('Branch'),
            '#options' => $branch_options,
            '#default_value' => $selected_branch,
            '#ajax' => [
                'callback' => '::updateStatsCallback',
                'wrapper' => 'accounts-wrapper',
                'event' => 'change',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['coop-select']],
        ];

        $stored_total_coop = $tempstore->get('total_coop_dropdown');
        $selected_total_coop = $form_state->getValue('total_coop_dropdown') ?? $request->query->get('total_coop_dropdown', $stored_total_coop ?? '');

        $form['stats-container']['total-coop-container']['filter'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['total-coop-filter-container']],
        ];
        $form['stats-container']['total-coop-container']['filter']['total_coop_dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('Cooperative'),
            '#options' => $coop_options,
            '#default_value' => $selected_total_coop,
            '#ajax' => [
                'callback' => '::updateTotalCoopCallback',
                'wrapper' => 'total-coop-wrapper',
                'event' => 'change',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['coop-select']],
        ];

        $form['stats-container']['accts-container']['bot-side'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-botside']],
            '#prefix' => '<div id="account-stats-wrapper">',
            '#suffix' => '</div>',
        ];

        $form['stats-container']['total-coop-container']['bot-side'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['total-coop-botside'],
            ],
        ];

        $total_indiv = $this->getTotalSubjectCount('individual');
        $total_company = $this->getTotalSubjectCount('company');
        $total_contracts = $this->getTotalContractCount();

        if ($selected_coop) {
            $total_indiv = $this->getSubjCountByCoop($selected_coop, 'individual');
            $total_company = $this->getSubjCountByCoop($selected_coop, 'company');
            $total_contracts = $this->getContractCountByCoop($selected_coop);
        }

        if ($selected_branch) {
            $branch = Node::load($selected_branch);
            if ($branch) {
                $branch_code = $branch->get('field_branch_code')->value;
                $total_indiv = $this->getSubjCountByBranch($branch_code, 'individual');
                $total_company = $this->getSubjCountByBranch($branch_code, 'company');
                $total_contracts = $this->getContractCountByBranch($branch_code);
            }
        }

        $charts_settings = $this->config('charts.settings');
        $library = $charts_settings->get('charts_default_settings.library');

        // Bar graph - Accounts
        $xaxis = [
            '#type' => 'chart_xaxis',
            '#labels' => [
                $this->t('Individual Accounts'),
                $this->t('Coop Accounts'),
                $this->t('Contract Accounts'),
            ],
        ];

        $yaxis = [
            '#type' => 'chart_yaxis',
        ];

        $series_one = [
            '#type' => 'chart_data',
            '#title' => $this->t(''),
            '#data' => [$total_indiv, $total_company, $total_contracts],
            '#color' => '#63B3ED',
        ];

        $chart = [
            '#type' => 'chart',
            '#chart_type' => 'column',
            '#chart_library' => $library,
            'series_one' => $series_one,
            'x_axis' => $xaxis,
            'y_axis' => $yaxis,
            '#raw_options' => [
                'options' => [
                    'legend' => ['position' => 'none'],
                    'height' => 300,
                    'width' => '50%',
                ],
            ],
        ];

        $form['stats-container']['accts-container']['bot-side']['stats'] = [
            '#markup' => \Drupal::service('renderer')->render($chart),
        ];

        // Dashboard table rows data - to be used in pie chart and table
        $rows = $this->getDashboardTableRows();
        $none_count = 0;
        $complete_count = 0;
        $partial_count = 0;

        $approved_count = 0;
        $pending_count = 0;
        $branch_none_count = 0;

        if ($selected_total_coop) {
            $coop_row = $rows[$selected_total_coop];
            if ($coop_row) {
                $branch_none_count = $coop_row['none_count'];
                $approved_count = $coop_row['approved_count'];
                $pending_count = $coop_row['pending_count'];
            }
        }

        foreach ($rows as $coop_nid => $row) {
            $current_status = $row['status'];

            switch ($current_status) {
                case 'None':
                    $none_count++;
                    break;
                case 'Complete':
                    $complete_count++;
                    break;
                case 'Partial':
                    $partial_count++;
                    break;
            }
        }

        //Pie chart - Total Cooperatives
        $xaxis = [
            '#type' => 'chart_xaxis',
        ];
        if ($selected_total_coop) {
            $xaxis['#labels'] = [
                $this->t('None'),
                $this->t('Pending'),
                $this->t('Approved'),
            ];
        } 
        else {
            $xaxis['#labels'] = [
                $this->t('None'),
                $this->t('Partial'),
                $this->t('Complete'),
            ];
        }

        $yaxis = [
            '#type' => 'chart_yaxis',
        ];

        $series_one = [
            '#type' => 'chart_data',
            '#title' => $this->t(''),
            '#color' => '#63B3ED',
        ];
        if ($selected_total_coop) {
            $series_one['#data'] = [$branch_none_count, $pending_count, $approved_count];
        }
        else {
            $series_one['#data'] = [$none_count, $partial_count, $complete_count];
        }

        $chart = [
            '#type' => 'chart',
            '#chart_type' => 'pie',
            '#chart_library' => $library,
            'series_one' => $series_one,
            'x_axis' => $xaxis,
            'y_axis' => $yaxis,
            '#raw_options' => [
                'options' => [
                    'legend' => ['position' => 'none'],
                    'height' => 300,
                    'width' => '50%',
                ],
            ],
        ];

        $form['stats-container']['total-coop-container']['bot-side']['stats'] = [
            '#markup' => \Drupal::service('renderer')->render($chart),
        ];

        $search = $form_state->getValue('search', '');

        if ($request->query->get('search') !== null && !$form_state->getUserInput()) {
            $search = $request->query->get('search');
        }
        
        $form['search_submit'] = [
            '#type' => 'submit',
            '#value' => 'Search',
            '#attributes' => ['style' => 'display:none;'],
        ];
        $form['table-filters'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['table-filters']],
        ];
        $form['table-filters']['search'] = [
            '#type' => 'textfield',
            '#default_value' => $search,
            '#size' => 30,
            '#attributes' => [
                'placeholder' => 'Search',
                'class' => ['searchbar'],
            ],
        ];

        $selected_status = $form_state->getValue('status-dropdown', '');

        if (empty($selected_status)) {
            $selected_status = $request->query->get('status-dropdown', '');
        }

        $form['table-filters']['status-dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('Status'),
            '#default_value' => $selected_status,
            '#options' => [
                'None' => $this->t('None'),
                'Partial' => $this->t('Partial'),
                'Complete' => $this->t('Complete'),
            ],
            '#attributes' => [
                'class' => ['status-dropdown'],
                'onchange' => 'this.form.submit()',
            ],
        ];

        $form['table'] = $this->buildDashboardTable($selected_status, $rows, $search);
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $request = \Drupal::request();

        $request->query->remove('page');

        $form_state->setRebuild(TRUE);
    }

    public function updateStatsCallback(array &$form, FormStateInterface $form_state) {
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $selected_coop = $form_state->getValue('coop_dropdown');
        if (!empty($selected_coop)) {
            $tempstore->set('coop_dropdown', $selected_coop);
        }

        $selected_branch = $form_state->getValue('branch_dropdown');
        if (!empty($selected_branch)) {
            $tempstore->set('branch_dropdown', $selected_branch);
        }
        return $form['stats-container']['accts-container'];
    }

    public function updateTotalCoopCallback(array &$form, FormStateInterface $form_state) {
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $selected_coop = $form_state->getValue('total_coop_dropdown');
        if (!empty($selected_coop)) {
            $tempstore->set('total_coop_dropdown', $selected_coop);
        }

        return $form['stats-container']['total-coop-container'];
    }

    private function getDashboardTableRows(): array {
        $rows = [];
        $current_month_start = strtotime(date('Y-m-01 00:00:00'));
        $current_month_end = strtotime(date('Y-m-t 23:59:59'));

        $all_coops = \Drupal::entityTypeManager()
            ->getStorage('node')
            ->loadByProperties([
                'type' => 'cooperative',
                'field_coop_status' => 1,
            ]);

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

            $latest_approved_per_branch = [];
            $latest_pending_per_branch = [];

            foreach ($uploads as $upload) {
                $branch_id = $upload->get('field_branch')->target_id ?? 'default';

                if (isset($latest_approved_per_branch[$branch_id])) {
                    continue;
                }

                if ($upload->get('field_status')->value === 'Approved') {
                    $latest_approved_per_branch[$branch_id] = $upload;
                }

                if ($upload->get('field_status')->value === 'Pending') {
                    $latest_pending_per_branch[$branch_id] = $upload;
                }
            }

            $approved_uploads_count = count($latest_approved_per_branch);
            $pending_uploads_count = count($latest_pending_per_branch);
            $total_branch_count = $this->getTotalBranchByCoop($coop_id);
            $none_count = $total_branch_count - ($approved_uploads_count + $pending_uploads_count);

            $branch_column = "{$approved_uploads_count}/{$total_branch_count} approved";
            $status = $approved_uploads_count === $total_branch_count ? 'Complete' : 'Partial';
            $status = $approved_uploads_count === 0 ? 'None' : $status;
            $rows[$coop_id] = [
                'coop_name' => $coop_name,
                'branch_column' => $branch_column,
                'status' => $status,
                'none_count' => $none_count,
                'approved_count' => $approved_uploads_count,
                'pending_count' => $pending_uploads_count,
            ];
        }
        return $rows;
    }

    private function buildDashboardTable(string $selected_status, array $rows, string $search = '') {
        $limit = 10;
        $rows = array_values($rows);

        $request = \Drupal::request();
        $query = $request->query->all();

        if (strlen(trim($search)) === 0 && $request->query->has('search')) {
            $request->query->remove('search');
            unset($query['search']);
        } else {
            $query['search'] = $search;
        }
        
        if (!empty($selected_status)) {
            $query['status-dropdown'] = $selected_status;
        }

        if (!empty($search) || !empty($selected_status)) {
            $search_lower = strtolower($search);
            
            $rows = array_filter($rows, function ($row) use ($search_lower, $selected_status) {
                $search_match = true;
                if (!empty($search_lower)) {
                    $search_match = str_contains(strtolower($row['coop_name']), $search_lower) ||
                                    str_contains(strtolower($row['branch_column']), $search_lower) ||
                                    str_contains(strtolower($row['status']), $search_lower);
                }

                $status_match = true;
                if (!empty($selected_status)) {
                    $status_match = ($row['status'] === $selected_status);
                }
            
                return $search_match && $status_match;
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
        if (!empty($selected_status)) {
            $pager_parameters['status'] = $selected_status; 
        }

        $build['pager'] = [
            '#type' => 'pager',
            '#theme' => 'views_mini_pager',
            '#parameters' => $pager_parameters,
        ];

        return $build;
    }
}