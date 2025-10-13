<?php
namespace Drupal\admin\Form;

use Drupal\common\Form\ConfirmActionForm;
use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Url;
use Drupal\admin\Component\CoopBranchesTable;
use Drupal\admin\Service\CooperativeService;

class CooperativeEditForm extends CooperativeBaseForm
{
  protected $confirm_modal;

  protected $cooperative_service;

  public function __construct(ConfirmActionForm $confirm_action_form, CooperativeService $cooperative_service)
  {
    $this->confirm_modal = $confirm_action_form;
    $this->cooperative_service = $cooperative_service;
  }

  public static function create(ContainerInterface $container)
  {
    return new static(
      $container->get('class_resolver')->getInstanceFromDefinition(ConfirmActionForm::class),
      $container->get('admin.cooperative_service')
    );
  }

  public function getFormId()
  {
    return 'mass_specc_cooperative_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {
    $form['#attached']['library'][] = 'admin/edit_coop_form_tabs';
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
    $request = \Drupal::request();
    $is_get = $request->getMethod() === 'GET';
    $is_ajax = $request->isXmlHttpRequest();

    if ($is_get && !$is_ajax) {
      $tempstore->delete((string) $id);
    }

    $form['#title'] = $this->t('Edit Cooperative');

    $form['header'] = [
      '#type' => 'container',
      '#attributes' => [
        'class' => ['form-header'],
      ],
      'back' => [
        '#markup' => '<a href="' . Url::fromRoute('cooperative.list')->toString() . '">
                                <i class="fas fa-arrow-left"></i>
                            </a>',
        '#prefix' => '<div class="back-button>',
        '#suffix' => '</div>',
      ],
      'title' => [
        '#markup' => '<h2 class="mb-0">' . $form['#title'] . '</h2>',
      ],
    ];

    $existing_coop = $id ? Node::load($id) : NULL;

    $status_active = $existing_coop && $existing_coop->get('field_coop_status')->value;

    if ($existing_coop) {
      $form['coop_id'] = [
        '#type' => 'hidden',
        '#value' => $id,
      ];
    }

    $form['nav'] = [
      '#type' => 'markup',
      '#markup' => '
      <div class="coop-nav">
        <a href="#" class="coop-tab active" data-target="#coop-general">General Info</a>
        <a href="#" class="coop-tab" data-target="#coop-branches">Branches</a>
      </div>
    ',
    ];

    $form['coop_general'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'coop-general',
        'class' => ['coop-section', 'active'],
      ],
    ];
    $this->buildCooperativeForm($form['coop_general'], $form_state, $existing_coop);

    $form['coop_branches'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'coop-branches',
        'class' => ['coop-section'],
      ],
    ];

    $branch_service = \Drupal::service('admin.branch_service');
    $saved_branches = $branch_service->getBranchesByCoop($id);

    $saved_branches_array = [];
    foreach ($saved_branches as $branch_node) {
      $saved_branches_array[$branch_node->id()] = [
        'branch_id' => $branch_node->id(),
        'branch_code' => $branch_node->get('field_branch_code')->value,
        'branch_name' => $branch_node->get('field_branch_name')->value,
        'email' => $branch_node->get('field_branch_email')->value,
        'contact_person' => $branch_node->get('field_branch_contact_person')->value,
        'is_staged' => FALSE,
      ];
    }

    $staged = $tempstore->get($id) ?? [];

    $all_branches = $saved_branches_array;
    foreach ($staged as $branch_id => $branch_data) {
      $key = !empty($branch_data['branch_id'])
        ? $branch_data['branch_id']
        : $branch_id;

      $all_branches[$key] = $branch_data + ['is_staged' => TRUE];
    }


    $form['coop_branches']['branches_table_wrapper'] = [
      '#type' => 'container',
      '#attributes' => ['id' => 'branches-table-wrapper'],
      'table' => CoopBranchesTable::render($id, $all_branches, $status_active),
    ];


    $form['coop_branches']['add_branch'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Branch'),
      '#url' => Url::fromRoute('cooperative.branches.add', ['id' => $existing_coop->id()]),
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-primary', $status_active ? '' : 'disabled'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(['width' => 800]),
      ],
    ];


    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Cooperative'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['coop-save-btn', $status_active ? '' : 'disabled'],
      ],
    ];

    $active_tab = isset($_POST['coop_active_tab']) ? $_POST['coop_active_tab'] : 'general';


    if (!$status_active) {
      $this->disableFormElements($form);
    }

    if ($active_tab === 'general') {
      if ($status_active) {
        $form['actions']['deactivate'] = [
          '#type' => 'link',
          '#title' => $this->t('Deactivate'),
          '#url' => Url::fromRoute('cooperative.deactivate_confirm', [
            'id' => $existing_coop->id(),
          ], [
            'query' => [
              'service' => 'admin.cooperative_service',
              'method' => 'deactivateCooperative',
              'confirm_mode' => 'destructive',
              'redirect_route' => 'cooperative.list',
              'action_label' => 'Yes',
              'question' => 'You are deactivating this Cooperative. Are you sure you want to deactivate this cooperative?',
            ],
          ]),
          '#attributes' => [
            'class' => ['use-ajax', 'btn', 'btn-deactivate-coop'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => 600]),
          ],
        ];
      } else {
        $form['actions']['activate'] = [
          '#type' => 'link',
          '#title' => $this->t('Activate'),
          '#url' => Url::fromRoute('cooperative.activate_confirm', [
            'id' => $existing_coop->id(),
          ], [
            'query' => [
              'service' => 'admin.cooperative_service',
              'method' => 'activateCooperative',
              'redirect_route' => 'cooperative.list',
              'action_label' => 'Yes',
              'question' => 'Are you sure you want to activate this cooperative?',
            ],
          ]),
          '#attributes' => [
            'class' => ['use-ajax', 'btn', 'btn-activate-coop'],
            'data-dialog-type' => 'modal',
            'data-dialog-options' => json_encode(['width' => 600]),
            'style' => 'margin-left: 10px;',
          ],
        ];
      }
    }

    $form['active_tab'] = [
      '#type' => 'hidden',
      '#value' => 'general',
      '#attributes' => ['id' => 'coop-active-tab'],
    ];

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state)
  {
    $coop_id = $form_state->getValue('coop_id') ?? ($form_state->getBuildInfo()['args'][0] ?? NULL);
    $node = $coop_id ? Node::load($coop_id) : NULL;

    if (!$node || $node->bundle() !== 'cooperative') {
      \Drupal::messenger()->addError($this->t('Invalid cooperative node.'));
      return;
    }

    $values = $form_state->getValues();

    try {
      $node->setTitle($values['coop_name'] ?? $node->getTitle());
      $node->set('field_coop_name', $values['coop_name'] ?? NULL);
      $node->set('field_coop_code', $values['coop_code'] ?? NULL);
      $node->set('field_cic_provider_code', $values['cic_provider_code'] ?? NULL);
      $node->set('field_ho_address', $values['ho_address'] ?? NULL);
      $node->set('field_no_of_employees', $values['no_of_employees'] ?? NULL);
      $node->set('field_contact_person', $values['contact_person'] ?? NULL);
      $node->set('field_coop_contact_number', $values['coop_contact_number'] ?? NULL);
      $node->set('field_email', $values['email'] ?? NULL);
      $node->set('field_cda_registration_date', $values['cda_registration_date'] ?? NULL);
      $node->set('field_cda_firm_size', $values['cda_firm_size'] ?? NULL);
      $node->set('field_assigned_report_templates', $values['assigned_report_templates'] ?? NULL);
      $node->save();

      $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
      $branches = $tempstore->get($coop_id) ?? [];

      foreach ($branches as $key => $branch_data) {
        $branch = NULL;

        if (!empty($branch_data['branch_id'])) {
          $branch = Node::load($branch_data['branch_id']);
        }

        if (!$branch && !empty($branch_data['branch_code'])) {
          $existing_nid = \Drupal::entityQuery('node')
            ->condition('type', 'branch')
            ->condition('field_branch_coop', $node->id())
            ->condition('field_branch_code', $branch_data['branch_code'])
            ->accessCheck(FALSE)
            ->execute();

          if (!empty($existing_nid)) {
            $branch = Node::load(reset($existing_nid));
          }
        }

        if (!$branch) {
          $branch = Node::create(['type' => 'branch']);
        }

        $branch->setTitle($branch_data['branch_name'] ?? '');
        $branch->set('field_branch_code', $branch_data['branch_code'] ?? NULL);
        $branch->set('field_branch_name', $branch_data['branch_name'] ?? NULL);
        $branch->set('field_branch_address', $branch_data['branch_address'] ?? NULL);
        $branch->set('field_branch_contact_person', $branch_data['contact_person'] ?? NULL);
        $branch->set('field_branch_contact_number', $branch_data['contact_number'] ?? NULL);
        $branch->set('field_branch_email', $branch_data['email'] ?? NULL);
        $branch->set('field_branch_no_of_employees', $branch_data['no_of_employees'] ?? NULL);
        $branch->set('field_branch_manager', $branch_data['branch_manager'] ?? NULL);
        $branch->set('field_branch_manager_contact_no', $branch_data['branch_manager_contact_no'] ?? NULL);
        $branch->set('field_branch_number_of_members', $branch_data['no_of_members'] ?? NULL);
        $branch->set('field_branch_coop', [['target_id' => $node->id()]]);

        $branch->save();

        $branch_data['branch_id'] = $branch->id();
        $branches[$key] = $branch_data;
      }

      $tempstore->delete($coop_id);

      \Drupal::messenger()->addMessage($this->t('Cooperative and branches saved successfully.'));
    } catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      \Drupal::logger('mass_specc')->error('Error saving cooperative/branches: @msg', ['@msg' => $e->getMessage()]);
    }

    $form_state->setRedirect('cooperative.list');
  }

  /**
   * Recursively disable form elements.
   */
  protected function disableFormElements(array &$form)
  {
    foreach ($form as $key => &$element) {
      if (in_array($key, ['actions', 'submit', 'deactivate', 'activate'], TRUE)) {
        continue;
      }

      if (is_array($element)) {
        if (isset($element['#type']) && !in_array($element['#type'], ['markup', 'hidden', 'container'])) {
          $element['#disabled'] = TRUE;
        }

        $this->disableFormElements($element);
      }
    }
  }

  public function submitHandler(&$form, FormStateInterface $form_state)
  {
    $form_state->setRedirect('cooperative.list');
  }

}
