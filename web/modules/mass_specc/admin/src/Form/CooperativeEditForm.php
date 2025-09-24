<?php
namespace Drupal\admin\Form;

use Drupal\node\Entity\Node;
use Drupal\Core\Form\FormStateInterface;
use Drupal\views\Views;
use Drupal\user\Entity\User;

class CooperativeEditForm extends CooperativeBaseForm
{

  public function getFormId()
  {
    return 'mass_specc_cooperative_edit_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL)
  {
    $form['#attached']['library'][] = 'admin/edit_coop_form_tabs';

    $existing_coop = $id ? Node::load($id) : NULL;

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
    $trigger = $form_state->getTriggeringElement();

    if ($trigger && $trigger['#name'] === 'save_action' && $form_state->getValue('save_action') === 'branches') {

      $id = $form_state->getBuildInfo()['args'][0] ?? NULL;
      $node = $id ? Node::load($id) : NULL;

      if ($node) {
        $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
        $branches = $tempstore->get($node->id()) ?? [];
        \Drupal::logger('mass_specc')->notice('Branch data: <pre>@data</pre>', ['@data' => print_r($branches, TRUE)]);
        foreach ($branches as $branch_data) {
          if (!empty($branch_data['branch_id'])) {
            $branch = Node::load($branch_data['branch_id']);
          } else {
            $branch = Node::create(['type' => 'branch']);
          }

          $branch->setTitle($branch_data['branch_name']);
          $branch->set('field_branch_code', $branch_data['branch_code']);
          $branch->set('field_branch_name', $branch_data['branch_name']);
          $branch->set('field_branch_address', $branch_data['branch_address']);
          $branch->set('field_branch_contact_person', $branch_data['contact_person']);
          $branch->set('field_branch_contact_number', $branch_data['contact_number']);
          $branch->set('field_branch_email', $branch_data['email']);
          $branch->set('field_branch_cda_registration_da', $branch_data['cda_registration_date']);
          $branch->set('field_branch_cda_firm_size', $branch_data['cda_firm_size']);
          $branch->set('field_branch_no_of_employees', $branch_data['no_of_employees']);
          $branch->set('field_branch_coop', [['target_id' => $node->id()]]);
          $branch->save();
        }

        $tempstore->delete($node->id());
        \Drupal::messenger()->addMessage($this->t('Branches saved successfully.'));
      }
    }

    $values = $form_state->getValues();
    $id = $form_state->getBuildInfo()['args'][0] ?? NULL;
    $node = $id ? Node::load($id) : NULL;

    if ($node && $node->bundle() === 'cooperative') {
      try {
        $node->setTitle($values['coop_name']);
        $node->set('field_coop_name', $values['coop_name']);
        $node->set('field_coop_code', $values['coop_code']);
        $node->set('field_cic_provider_code', $values['cic_provider_code']);
        $node->set('field_ho_address', $values['ho_address']);
        $node->set('field_no_of_employees', $values['no_of_employees']);
        $node->set('field_contact_person', $values['contact_person']);
        $node->set('field_coop_contact_number', $values['coop_contact_number']);
        $node->set('field_email', $values['email']);
        $node->set('field_cda_registration_date', $values['cda_registration_date']);
        $node->set('field_cda_firm_size', $values['cda_firm_size']);
        $node->set('field_assigned_report_templates', $values['assigned_report_templates']);
        $node->save();

        $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
        $branches = $tempstore->get($node->id()) ?? [];

        \Drupal::logger('mass_specc')->notice('Branch data: <pre>@data</pre>', ['@data' => print_r($branches, TRUE)]);
        foreach ($branches as $branch_data) {
          if (!empty($branch_data['branch_id']) && $branch = Node::load($branch_data['branch_id'])) {
            $branch = Node::load($branch_data['branch_id']);
          } else {
            $branch = Node::create(['type' => 'branch']);
          }

          $branch->setTitle($branch_data['branch_name']);
          $branch->set('field_branch_code', $branch_data['branch_code']);
          $branch->set('field_branch_name', $branch_data['branch_name']);
          $branch->set('field_branch_address', $branch_data['branch_address']);
          $branch->set('field_branch_contact_person', $branch_data['contact_person']);
          $branch->set('field_branch_contact_number', $branch_data['contact_number']);
          $branch->set('field_branch_email', $branch_data['email']);
          $branch->set('field_branch_cda_registration_da', $branch_data['cda_registration_date']);
          $branch->set('field_branch_cda_firm_size', $branch_data['cda_firm_size']);
          $branch->set('field_branch_no_of_employees', $branch_data['no_of_employees']);
          $branch->set('field_branch_coop', [['target_id' => $node->id()]]);
          $branch->save();
        }
        $tempstore->delete($node->id());

        \Drupal::messenger()->addMessage($this->t('Cooperative and branches saved successfully.'));
      } catch (\Exception $e) {
        \Drupal::messenger()->addError($this->t('Error: @message', ['@message' => $e->getMessage()]));
      }
    } else {
      \Drupal::messenger()->addError($this->t('Invalid cooperative node.'));
    }

    $form_state->setRedirect('cooperative.list');
    return;
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
