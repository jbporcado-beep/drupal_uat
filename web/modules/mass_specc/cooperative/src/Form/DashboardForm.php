<?php
namespace Drupal\cooperative\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\node\Entity\Node;
use Drupal\Core\Url;
use Drupal\user\Entity\User;

class DashboardForm extends FormBase {

    public function getFormId() {
        return 'coop_dashboard_form';
    }

    private function getTotalSubjectCount(string $subject): int {
        $user_session = \Drupal::currentUser();
        $is_approver = $user_session->hasRole('approver');
        $uid = $user_session->id();
        $user_entity = User::load($uid);
        $coop_nid = $user_entity->get('field_cooperative')->target_id;
        $coop_entity = Node::load($coop_nid);
        $provider_code = $coop_entity?->get('field_cic_provider_code')->value;

        if ($is_approver) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', $subject)
                ->condition('field_provider_code', $provider_code)
                ->accessCheck(FALSE);
            $count = $query->count()->execute();
            return $count;
        }
        else { 
            $branch_nid = $user_entity->get('field_branch')->target_id;
            $branch_entity = Node::load($branch_nid);
            $branch_code = $branch_entity->get('field_branch_code')->value;

            $query = \Drupal::entityQuery('node')
                ->condition('type', $subject)
                ->condition('field_branch_code', $branch_code)
                ->accessCheck(FALSE);
            return $query->count()->execute();
        }
    }

    private function getTotalContractCount(): int {
        $user_session = \Drupal::currentUser();
        $is_approver = $user_session->hasRole('approver');
        $uid = $user_session->id();
        $user_entity = User::load($uid);
        $coop_nid = $user_entity->get('field_cooperative')->target_id;
        $coop_entity = Node::load($coop_nid);
        $provider_code = $coop_entity->get('field_cic_provider_code')->value;

        $ci_count = 0;
        $cn_count = 0;
        if ($is_approver) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'installment_contract')
                ->condition('field_header.entity.field_provider_code', $provider_code)
                ->accessCheck(FALSE);
            $ci_count = $query->count()->execute();
            
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'noninstallment_contract')
                ->condition('field_header.entity.field_provider_code', $provider_code)
                ->accessCheck(FALSE);
            $cn_count = $query->count()->execute();
        }
        else {
            $branch_nid = $user_entity->get('field_branch')->target_id;
            $branch_entity = Node::load($branch_nid);
            $branch_code = $branch_entity->get('field_branch_code')->value;

            $query = \Drupal::entityQuery('node')
                ->condition('type', 'installment_contract')
                ->condition('field_header.entity.field_branch_code', $branch_code)
                ->accessCheck(FALSE);
            $ci_count = $query->count()->execute();
            
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'noninstallment_contract')
                ->condition('field_header.entity.field_branch_code', $branch_code)
                ->accessCheck(FALSE);
            $cn_count = $query->count()->execute();
        }

        return $ci_count + $cn_count;
    }

    private function getAllBranchUnderUserCoop(): array {
        $user_session = \Drupal::currentUser();
        $is_approver = $user_session->hasRole('approver');
        $uid = $user_session->id();
        $user_entity = User::load($uid);
        $coop_nid = $user_entity->get('field_cooperative')->target_id;

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'branch')
            ->condition('field_branch_coop', $coop_nid)
            ->accessCheck(FALSE);

        $nids = $query->execute();

        return $nids;
    }

    private function getSubjCountByBranch($branch_nid, $subject): int {
        $branch_node = Node::load($branch_nid);
        $branch_code = '';
        if ($branch_node) {
            $branch_code = $branch_node->get('field_branch_code')->value;
        }

        if (empty($branch_code)) {
            return 0;
        }

        $query = \Drupal::entityQuery('node')
            ->condition('type', $subject)
            ->condition('field_branch_code', $branch_code)
            ->accessCheck(FALSE);
        return $query->count()->execute();
    }

    private function getContractCountByBranch($branch_nid): int {
        $branch_node = Node::load($branch_nid);
        $branch_code = '';
        if ($branch_node) {
            $branch_code = $branch_node->get('field_branch_code')->value;
        }

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
        $form['#attached']['library'][] = 'cooperative/dashboard_autosubmit';

        $form['#method'] = 'GET';

        $user = \Drupal::currentUser();
        $is_approver = $user->hasRole('approver');

        $form['accts-container'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['accts-container']],
        ];
        $form['accts-container']['top-side'] = [
            '#type' => 'container',
            '#attributes' => ['class' => ['acct-topside']],
        ];
        $form['accts-container']['top-side']['header'] = [
            '#type' => 'markup',
            '#markup' => "<h4 class='coop-stat-header'>Accounts</h4>",
        ];

        $branch_nids = $this->getAllBranchUnderUserCoop();
        $branch_options = [];

        if (!empty($branch_nids)) {
            $branches = Node::loadMultiple($branch_nids);

            foreach ($branches as $branch) {
                if ($branch->hasField('field_branch_name')) {
                    $branch_options[$branch->id()] = $branch->get('field_branch_name')->value;
                }
            }
        }

        $request = \Drupal::request();
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $stored = $tempstore->get('branch_dropdown');
        $selected_branch = $form_state->getValue('branch_dropdown') ?? $request->query->get('branch_dropdown', $stored ?? '');

        $form['accts-container']['top-side']['branch_dropdown'] = [
            '#type' => 'select',
            '#empty_option' => $this->t('- Select a branch -'),
            '#options' => $branch_options,
            '#default_value' => $selected_branch,
            '#ajax' => [
                'callback' => '::updateStatsCallback',
                'wrapper' => 'account-stats-wrapper',
                'event' => 'change',
                'progress' => ['type' => 'none']
            ],
            '#attributes' => ['class' => ['coop-select']],
            '#access' => $is_approver,
        ];

        $form['accts-container']['bot-side'] = [
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

        if ($selected_branch && $is_approver) {
            $total_indiv = $this->getSubjCountByBranch($selected_branch, 'individual');
            $total_company = $this->getSubjCountByBranch($selected_branch, 'company');
            $total_contracts = $this->getContractCountByBranch($selected_branch);
            $total = $total_indiv + $total_company + $total_contracts;
        }

        $form['accts-container']['bot-side']['stats'] = [
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

        if ($is_approver) {
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
                    'Approved' => $this->t('Approved'),
                    'Pending' => $this->t('Pending'),
                    'Not yet uploaded' => $this->t('Not yet uploaded'),
                ],
                '#attributes' => [
                    'class' => ['status-dropdown'],
                    'onchange' => 'this.form.submit()',
                ],
            ];
            
            $form['table'] = $this->buildDashboardTable($selected_status, $search);
        }
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $request = \Drupal::request();

        $request->query->remove('page');

        $form_state->setRebuild(TRUE);
    }

    public function updateStatsCallback(array &$form, FormStateInterface $form_state) {
        $selected = $form_state->getValue('branch_dropdown');
        $tempstore = \Drupal::service('tempstore.private')->get('dashboard_store');
        $tempstore->set('branch_dropdown', $selected);
        return $form['accts-container']['bot-side'];
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

        $all_branch_nids = $this->getAllBranchUnderUserCoop();

        foreach ($all_branch_nids as $branch_nid) {
            $branch = Node::load($branch_nid);
            $branch_id = $branch->id();
            $branch_name = $branch->get('field_branch_name')->value;

            $query = \Drupal::entityQuery('node')
                ->condition('type', 'file_upload_history')
                ->condition('field_branch', $branch_id)
                ->condition('created', [$current_month_start, $current_month_end], 'BETWEEN')
                ->sort('created', 'DESC')
                ->accessCheck(FALSE)
                ->range(0, 1);

            $result = $query->execute();
            $upload_nid = reset($result);
            $upload = !empty($upload_nid) ? Node::load($upload_nid) : NULL;
            $status = "Not yet uploaded";

            if ($upload) {
                $status = $upload->get('field_status')->value;
            }

            $rows[] = [
                'branch_column' => $branch_name,
                'status' => $status,
            ];
        }

        if (!empty($search) || !empty($selected_status)) {
            $search_lower = strtolower($search);
            
            $rows = array_filter($rows, function ($row) use ($search_lower, $selected_status) {
                $search_match = true;
                if (!empty($search_lower)) {
                    $search_match = str_contains(strtolower($row['branch_column']), $search_lower) ||
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

        $build['dashboard_table'] = [
            '#type' => 'table',
            '#header' => ['Branches', 'Status'],
            '#attributes' => [
                'class' => ['coop-summary-table'],
            ],
            '#rows' => array_map(function ($row) {
            return [
                'data' => [
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