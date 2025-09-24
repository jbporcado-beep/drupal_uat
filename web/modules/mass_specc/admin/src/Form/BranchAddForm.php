<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Ajax\AjaxResponse;
use Drupal\Core\Ajax\InvokeCommand;
use Drupal\Core\Ajax\MessageCommand;
use Drupal\Core\Ajax\CloseDialogCommand;
use Drupal\node\Entity\Node;
use Drupal\Core\Ajax\ReplaceCommand;

class BranchAddForm extends FormBase {

  public function getFormId() {
    return 'branch_add_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state, $id = NULL, $branch_id = NULL) {
    $form['#attached']['library'][] = 'core/drupal.dialog.ajax';
    $form['#attached']['library'][] = 'common/char-count';
    $form['#attached']['library'][] = 'admin/branch_save';

    $form['#prefix'] = '<div id="branch-form-wrapper">';

    $existing_branch = NULL;
    
    $form['branch_code'] = [
      '#type'=>'textfield',
      '#title'=>$this->t('Branch Code'),
      '#required'=>TRUE,
      '#attributes'=>[
        'class'=>['js-char-count'],
        'data-maxlength'=>20,
      ],
      '#description'=>[
        '#markup'=>'<span class="char-counter">0/20</span>',
      ],
      '#maxlength'=>20,
    ];

    $form['branch_name'] = [
      '#type'=>'textfield',
      '#title'=>$this->t('Branch Name'),
      '#required'=>TRUE,
      '#attributes'=>[
        'class'=>['js-char-count'],
        'data-maxlength'=>100,
      ],
      '#description'=>[
        '#markup'=>'<span class="char-counter">0/100</span>',
      ],
      '#maxlength'=>100,
    ];

    $form['branch_address'] = [
      '#type'=>'textarea',
      '#title'=>$this->t('Branch Address'),
      '#required'=>TRUE,
      '#attributes'=>[
        'class'=>['js-char-count'],
        'data-maxlength'=> 200,
      ],
      '#description'=>[
        '#markup'=>'<span class="char-counter">0/200</span>',
      ],
      '#maxlength'=>200,
    ];

    $form['contact_person'] = [
      '#type'=>'textfield',
      '#title'=>$this->t('Contact Person'),
      '#required'=>TRUE,
      '#attributes'=>[
        'class'=>['js-char-count'],
        'data-maxlength'=>100,
      ],
      '#description'=>[
        '#markup'=>'<span class="char-counter">0/100</span>',
      ],
      '#maxlength'=>100,
    ];

    $form['contact_number'] = [
      '#type'=>'textfield',
      '#title'=>$this->t('Contact Number'),
      '#required'=>TRUE,
      '#attributes'=>[
        'data-maxlength' => 11,
        'maxlength' => 11,
        'inputmode' => 'numeric',
        'pattern' => '[0-9]*',
      ],
      '#description'=>$this->t('Format: 09XXXXXXXXX'),
      '#maxlength'=>11,
      '#element_validate' => [
                ['\Drupal\admin\Form\CooperativeBaseForm', 'validatePhMobileElement'],
      ],
    ];


    $form['email'] = [
      '#type'=>'email',
      '#title'=>$this->t('Email'),
      '#required'=>TRUE,
    ];

    $form['cda_group'] = [
            "#type" => 'container',
            '#attributes' => ['class' => ['coop-cda-group'] ],
        ];

    $form['cda_group']['cda_registration_date'] = [
      '#type'=>'date',
      '#title'=>$this->t('CDA Registration Date'),
      '#required'=>TRUE,
    ];

    $form['cda_group']['cda_firm_size'] = [
      '#type'=>'number',
      '#title'=>$this->t('CDA Firm Size'),
      '#required'=>TRUE,
      '#min'=>0,
      '#max'=> 9999,
    ];

    $form['no_of_employees'] = [
      '#type'=>'number',
      '#title'=>$this->t('Number of Employees in Head Office'),
      '#required'=>TRUE,
      '#min'=>0,
      '#max'=> 9999,
    ];

    $form['coop_id'] = ['#type'=>'hidden','#value'=>$id];
    $form['branch_id'] = ['#type' => 'hidden', '#value' => $branch_id];

    if ($branch_id) {
      $existing_branch = Node::load($branch_id);
      if ($existing_branch) {
        $form['branch_code']['#default_value'] = $existing_branch->get('field_branch_code')->value;
        $form['branch_name']['#default_value'] = $existing_branch->get('field_branch_name')->value;
        $form['branch_address']['#default_value'] = $existing_branch->get('field_branch_address')->value;
        $form['contact_person']['#default_value'] = $existing_branch->get('field_branch_contact_person')->value;
        $form['contact_number']['#default_value'] = $existing_branch->get('field_branch_contact_number')->value;
        $form['email']['#default_value'] = $existing_branch->get('field_branch_email')->value;
        $form['cda_group']['cda_registration_date']['#default_value'] = $existing_branch->get('field_branch_cda_registration_da')->value;
        $form['cda_group']['cda_firm_size']['#default_value'] = $existing_branch->get('field_branch_cda_firm_size')->value;
        $form['no_of_employees']['#default_value'] = $existing_branch->get('field_branch_no_of_employees')->value;
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type'=>'submit',
      '#value'=>$existing_branch ? $this->t('Save Changes') : $this->t('Create Branch'),
      '#button_type'=>'primary',
      '#ajax'=>[
        'callback'=>'::ajaxSubmit',
        'wrapper'=>'branches-table-wrapper',
        'effect'=>'fade',
      ],
    ];

    $form['#suffix'] = '</div>';

    // $form['#validate'][] = '::validateForm';

    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
      $coop_id = $form_state->getValue('coop_id');
      $branch_id = $form_state->getValue('branch_id');
      $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');

      $branches = $tempstore->get($coop_id) ?? [];

      $branch_data = [
        'branch_id' => $branch_id ?: NULL,
        'branch_code' => $form_state->getValue('branch_code'),
        'branch_name' => $form_state->getValue('branch_name'),
        'branch_address' => $form_state->getValue('branch_address'),
        'contact_person' => $form_state->getValue('contact_person'),
        'contact_number' => $form_state->getValue('contact_number'),
        'email' => $form_state->getValue('email'),
        'cda_registration_date' => $form_state->getValue('cda_registration_date'),
        'cda_firm_size' => $form_state->getValue('cda_firm_size'),
        'no_of_employees' => $form_state->getValue('no_of_employees'),
      ];

      \Drupal::logger('mass_specc')->notice('BranchAddForm submitted data: <pre>@data</pre>', [
        '@data' => print_r($branch_data, TRUE),
      ]);

      if ($branch_id !== NULL && $branch_id !== '') {
        $branches[$branch_id] = $branch_data;
      } else {
        $branches[] = $branch_data;
      }

      $tempstore->set($coop_id, $branches);
      $form_state->setRebuild(TRUE);
  }

   public function ajaxSubmit(array &$form, FormStateInterface $form_state) {
      $response = new AjaxResponse();

      if ($form_state->hasAnyErrors()) {
        $response->addCommand(new ReplaceCommand(
          '#branch-form-wrapper',
          $form
        ));
        return $response;
      }

      $coop_id = $form_state->getValue('coop_id');
      $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
      $staged = $tempstore->get($coop_id) ?? [];

      \Drupal::logger('mass_specc')->notice('Using coop_id in ajax submit: @id', ['@id' => $coop_id]);
      \Drupal::logger('mass_specc')->notice('Staged branches after branch AJAX submit: <pre>@data</pre>', [
        '@data' => print_r($staged, TRUE),
      ]);

      $staged_rows = '';
      foreach ($staged as $branch) {
          $staged_rows .= '<tr class="staged-branch">';
          $staged_rows .= '<td>' . htmlspecialchars($branch['branch_code']) . '</td>';
          $staged_rows .= '<td>' . htmlspecialchars($branch['branch_name']) . '</td>';
          $staged_rows .= '<td>' . htmlspecialchars($branch['email']) . '</td>';
          $staged_rows .= '<td>' . htmlspecialchars($branch['contact_person']) . '</td>';
          $staged_rows .= '<td><span class="text-muted">Staged (not yet saved)</span></td>';
          $staged_rows .= '</tr>';
      }

      if ($staged_rows) {
          $response->addCommand(new InvokeCommand(
              '#branches-wrapper tbody',
              'append',
              [$staged_rows]
          ));
      }

      $response->addCommand(new MessageCommand(
          $this->t('Updated branch information. Please save to confirm changes.'),
          NULL,
          ['type' => 'status']
      ));

      $response->addCommand(new InvokeCommand(
          '#save-branches-btn',
          'prop',
          ['disabled', false]
      ));

      $response->addCommand(new CloseDialogCommand());

      return $response;
  }

  public function validateForm(array &$form, FormStateInterface $form_state) {
      $branch_code = $form_state->getValue('branch_code');
      $coop_id = $form_state->getValue('coop_id');
      $branch_id = $form_state->getValue('branch_id');


      $tempstore = \Drupal::service('tempstore.private')->get('coop_branches');
      $staged_branches = $tempstore->get($coop_id) ?? [];
      foreach ($staged_branches as $id => $branch) {
          if ($branch['branch_code'] === $branch_code && $id != $branch_id) {
              $form_state->setErrorByName('branch_code', $this->t('The Branch Code %code is already staged.', ['%code' => $branch_code]));
              break;
          }
      }

      $query = \Drupal::entityQuery('node')
          ->condition('type', 'branch')
          ->condition('field_branch_code', $branch_code);
      if ($branch_id) {
          $query->condition('nid', $branch_id, '<>');
      }
      $existing = $query->accessCheck(FALSE)->execute();
      if (!empty($existing)) {
          $form_state->setErrorByName('branch_code', $this->t('The Branch Code %code is already in use.', ['%code' => $branch_code]));
      }
  }
}
