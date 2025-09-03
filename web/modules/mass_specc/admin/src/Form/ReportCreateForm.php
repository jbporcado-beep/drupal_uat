<?php
namespace Drupal\admin\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\Form\FormStateInterface;

class ReportCreateForm extends FormBase {
  public function getFormId() {
    return 'report_create_form';
  }

  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['title'] = [
      '#type' => 'textfield',
      '#title' => $this->t('Report Title'),
      '#required' => TRUE,
    ];

    $form['description'] = [
      '#type' => 'textarea',
      '#title' => $this->t('Description'),
      '#required' => FALSE,
    ];

    $form['date'] = [
      '#type' => 'date',
      '#title' => $this->t('Report Date'),
      '#required' => TRUE,
    ];

    $form['status'] = [
      '#type' => 'select',
      '#title' => $this->t('Status'),
      '#options' => [
        'draft' => $this->t('Draft'),
        'published' => $this->t('Published'),
      ],
      '#required' => TRUE,
    ];

    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Create Report'),
    ];
    return $form;
  }

  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Dummy submit handler.
    \Drupal::messenger()->addMessage($this->t('Dummy report created: @title', [
      '@title' => $form_state->getValue('title'),
    ]));
  }
}