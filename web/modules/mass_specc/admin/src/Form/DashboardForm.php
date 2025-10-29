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

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/dashboard';
        $form['#attached']['library'][] = 'admin/dashboard_autosubmit';

        $form['#method'] = 'GET';

        $all_active_coops = $this->getAllActiveCoops();
        $coop_count = count($all_active_coops);

        $form['stats-container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['stats-container']],
        ];
        $form['stats-container']['accts-container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['accts-container']],
        ];
        $form['stats-container']['accts-container']['top-side'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-topside']],
        ];
        $form['stats-container']['accts-container']['top-side']['header'] = [
            '#type' => 'markup',
            '#markup' => "<h4 class='stat-header'>Accounts</h4>",
        ];

        $request = \Drupal::request();
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $stored = $tempstore->get('coop_dropdown');
        $selected_coop = $form_state->getValue('coop_dropdown') ?? $request->query->get('coop_dropdown', $stored ?? '');

        $form['stats-container']['accts-container']['top-side']['coop_dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('- Select a cooperative -'),
            '#options' => $all_active_coops,
            '#default_value' => $selected_coop,
            '#ajax' => [
                'callback' => '::updateStatsCallback',
                'wrapper' => 'account-stats-wrapper',
                'event' => 'change',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['coop-select']],
        ];

        $form['stats-container']['accts-container']['bot-side'] = [
            '#type' => 'container',
            '#attributes' => [
                'class' => ['acct-botside'],
                'id' => 'account-stats-wrapper',
            ],
            '#prefix' => '<div id="account-stats-wrapper">',
            '#suffix' => '</div>',
        ];

        $total_indiv = $this->getTotalSubjectCount('individual');
        $total_company = $this->getTotalSubjectCount('company');
        $total_contracts = $this->getTotalContractCount();
        $total = $total_indiv + $total_company + $total_contracts;

        if ($selected_coop) {
            $total_indiv = $this->getSubjCountByCoop($selected_coop, 'individual');
            $total_company = $this->getSubjCountByCoop($selected_coop, 'company');
            $total_contracts = $this->getContractCountByCoop($selected_coop);
            $total = $total_indiv + $total_company + $total_contracts;
        }

        $form['stats-container']['accts-container']['bot-side']['stats'] = [
            '#type' => 'markup',
            '#markup' => "
                <div class='account-stat'>
                    <p class='left-p'>Individual Accounts</p>
                    <p class='right-p'>{$total_indiv}</p>
                </div>
                <div class='account-stat'>
                    <p class='left-p'>Coop Accounts</p>
                    <p class='right-p'>{$total_company}</p>
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
        $form['stats-container']['total-coop'] = [
            '#type' => 'markup',
            '#markup' => "
                <div class='total-coop-container'>
                    <div class='total-coop'>
                        <h5>No. of Cooperatives: {$coop_count}</h5>
                    </div>
                </div>
            ",
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
                'Complete' => $this->t('Complete'),
                'Partial' => $this->t('Partial'),
            ],
            '#attributes' => [
                'class' => ['status-dropdown'],
                'onchange' => 'this.form.submit()',
            ],
        ];


        $form['table'] = $this->buildDashboardTable($selected_status, $search);
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $request = \Drupal::request();

        $request->query->remove('page');

        $form_state->setRebuild(TRUE);
    }

    public function updateStatsCallback(array &$form, FormStateInterface $form_state) {
        $selected = $form_state->getValue('coop_dropdown');
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $tempstore->set('coop_dropdown', $selected);
        return $form['stats-container']['accts-container']['bot-side'];
    }

    private function buildDashboardTable(string $selected_status, string $search = '') {
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
        
        if (!empty($selected_status)) {
            $query['status-dropdown'] = $selected_status;
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