<?php
namespace Drupal\admin\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\user\Entity\User;
use Drupal\Core\Link;
use Drupal\Core\Url;

class CooperativeEditForm extends CooperativeBaseForm
{

  public function getFormId()
  {
    return 'mass_specc_cooperative_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {
    $form['#attached']['library'][] = 'admin/edit_coop_form_tabs';

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
    if ($existing_coop) {
      $view = Views::getView('branches_list');
      if ($view) {
        $view->setDisplay('branches_table');
        $view->setArguments([$existing_coop->id()]);
        $view->execute();

        $form['coop_branches']['list'] = $view->render();
      }
    }
    $form['coop_branches']['add_branch'] = [
      '#type' => 'link',
      '#title' => $this->t('Add Branch'),
      '#url' => \Drupal\Core\Url::fromRoute('cooperative.branches.add', ['id' => $existing_coop->id()]),
      '#attributes' => [
        'class' => ['use-ajax', 'btn', 'btn-primary'],
        'data-dialog-type' => 'modal',
        'data-dialog-options' => json_encode(['width' => 800]),
      ],
    ];
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Save Cooperative'),
      '#button_type' => 'primary',
      '#attributes' => [
        'class' => ['coop-save-btn'],
      ],
    ];

    $active_tab = isset($_POST['coop_active_tab']) ? $_POST['coop_active_tab'] : 'general';
    $status_active = $existing_coop && $existing_coop->get('field_coop_status')->value;

    if ($active_tab === 'general') {
      if ($status_active) {

        $form['actions']['deactivate'] = [
          '#type' => 'submit',
          '#value' => $this->t('Deactivate'),
          '#button_type' => 'danger',
          '#attributes' => [
            'class' => ['btn-danger', 'btn-deactivate-coop'],
            'style' => 'margin-left: 10px;',
          ],
          '#submit' => ['::deactivateCooperative'],
          '#confirm' => $this->t('Are you sure you want to deactivate this cooperative? This action cannot be undone.'),
        ];
      } else {

        $form['actions']['activate'] = [
          '#type' => 'submit',
          '#value' => $this->t('Activate'),
          '#button_type' => 'success',
          '#attributes' => [
            'class' => ['btn-success', 'btn-activate-coop'],
            'style' => 'margin-left: 10px;',
          ],
          '#submit' => ['::activateCooperative'],
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

      foreach ($branches as $branch_data) {
        $branch_id = !empty($branch_data['branch_id']) ? $branch_data['branch_id'] : NULL;
        $branch = $branch_id ? Node::load($branch_id) : Node::create(['type' => 'branch']);

        $branch->setTitle($branch_data['branch_name'] ?? '');
        $branch->set('field_branch_code', $branch_data['branch_code'] ?? NULL);
        $branch->set('field_branch_name', $branch_data['branch_name'] ?? NULL);
        $branch->set('field_branch_address', $branch_data['branch_address'] ?? NULL);
        $branch->set('field_branch_contact_person', $branch_data['contact_person'] ?? NULL);
        $branch->set('field_branch_contact_number', $branch_data['contact_number'] ?? NULL);
        $branch->set('field_branch_email', $branch_data['email'] ?? NULL);
        $branch->set('field_branch_cda_registration_da', $branch_data['cda_registration_date'] ?? NULL);
        $branch->set('field_branch_cda_firm_size', $branch_data['cda_firm_size'] ?? NULL);
        $branch->set('field_branch_no_of_employees', $branch_data['no_of_employees'] ?? NULL);
        $branch->set('field_branch_coop', [['target_id' => $node->id()]]);
        $branch->save();
      }

      $tempstore->delete($coop_id);

      \Drupal::messenger()->addMessage($this->t('Cooperative and branches saved successfully.'));
    } catch (\Exception $e) {
      \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      \Drupal::logger('mass_specc')->error('Error saving cooperative/branches: @msg', ['@msg' => $e->getMessage()]);
    }

    $form_state->setRedirect('cooperative.list');
  }


  public function deactivateCooperative(array &$form, FormStateInterface $form_state)
  {
    $id = $form_state->getBuildInfo()['args'][0] ?? NULL;
    $node = $id ? Node::load($id) : NULL;

    if ($node && $node->bundle() === 'cooperative') {
      $node->set('field_coop_status', FALSE);
      $node->save();


      $user_ids = \Drupal::entityQuery('user')
        ->condition('field_cooperative', $node->id())
        ->accessCheck(TRUE)
        ->execute();

      foreach ($user_ids as $uid) {
        $user = User::load($uid);
        if ($user) {
          $user->block();
          $user->save();
        }
      }

      \Drupal::messenger()->addMessage($this->t('Cooperative deactivated.'));
      $form_state->setRedirect('cooperative.list');
    } else {
      \Drupal::messenger()->addError($this->t('Invalid cooperative node.'));
    }
  }

  public function activateCooperative(array &$form, FormStateInterface $form_state)
  {
    $id = $form_state->getBuildInfo()['args'][0] ?? NULL;
    $node = $id ? Node::load($id) : NULL;

    if ($node && $node->bundle() === 'cooperative') {
      $node->set('field_coop_status', TRUE);
      $node->save();

      $user_ids = \Drupal::entityQuery('user')
        ->condition('field_cooperative', $node->id())
        ->accessCheck(TRUE)
        ->execute();

      foreach ($user_ids as $uid) {
        $user = User::load($uid);
        if ($user) {
          $user->activate();
          $user->save();
        }
      }

      \Drupal::messenger()->addMessage($this->t('Cooperative activated.'));
      $form_state->setRedirect('cooperative.list');
    } else {
      \Drupal::messenger()->addError($this->t('Invalid cooperative node.'));
    }
  }
  public function submitHandler(&$form, FormStateInterface $form_state)
  {
    $form_state->setRedirect('cooperative.list');
  }

}
