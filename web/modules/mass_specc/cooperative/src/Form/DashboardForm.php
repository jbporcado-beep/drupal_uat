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

    private function getTotalSubjectCount(): int {
        $user_session = \Drupal::currentUser();
        $is_approver = $user_session->hasRole('approver');
        $uid = $user_session->id();
        $user_entity = User::load($uid);
        $coop_nid = $user_entity->get('field_cooperative')->target_id;
        $coop_entity = Node::load($coop_nid);
        $provider_code = $coop_entity->get('field_cic_provider_code')->value;

        if ($is_approver) {
            $query = \Drupal::entityQuery('node')
                ->condition('type', 'individual')
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
                ->condition('type', 'individual')
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

    public function buildForm(array $form, FormStateInterface $form_state) {
        $form['#attached']['library'][] = 'mass_specc_bootstrap_sass/dashboard';
        $form['#attached']['library'][] = 'cooperative/dashboard_autosubmit';


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

        $user = \Drupal::currentUser();
        $is_approver = $user->hasRole('approver');
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
        }
        return $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state) {
        $request = \Drupal::request();

        $request->query->remove('page');

        $form_state->setRebuild(TRUE);
    }

    private function buildDashboardTable($search = '') {
        $limit = 3;
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

        if (!empty($search)) {
            $search_lower = strtolower($search);
            $rows = array_filter($rows, function ($row) use ($search_lower) {
            return
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

        $build['pager'] = [
            '#type' => 'pager',
            '#theme' => 'views_mini_pager',
            '#parameters' => $pager_parameters,
        ];

        return $build;
    }
}