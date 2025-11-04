<?php

namespace Drupal\login\Form;

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;

class LoginSettingsForm extends ConfigFormBase
{

    public function getFormId()
    {
        return 'login_settings_form';
    }

    protected function getEditableConfigNames()
    {
        return ['login.settings'];
    }

    public function buildForm(array $form, FormStateInterface $form_state)
    {
        $config = $this->config('login.settings');

        $form['block_every'] = [
            '#type' => 'number',
            '#title' => $this->t('Failures per cooldown (block every)'),
            '#description' => $this->t('Number of failed attempts before applying a cooldown (e.g. 5).'),
            '#default_value' => $config->get('block_every') ?? 5,
            '#min' => 1,
            '#required' => TRUE,
        ];

        $form['base_delay'] = [
            '#type' => 'number',
            '#title' => $this->t('Base cooldown (seconds)'),
            '#description' => $this->t('Initial cooldown duration (in seconds) applied after the first threshold).'),
            '#default_value' => $config->get('base_delay') ?? 30,
            '#min' => 1,
            '#required' => TRUE,
        ];

        $form['max_delay'] = [
            '#type' => 'number',
            '#title' => $this->t('Maximum cooldown (seconds)'),
            '#description' => $this->t('Maximum cooldown to clamp exponential backoff to (in seconds).'),
            '#default_value' => $config->get('max_delay') ?? 300,
            '#min' => 1,
            '#required' => TRUE,
        ];

        $form['reset_window'] = [
            '#type' => 'number',
            '#title' => $this->t('Reset window (seconds)'),
            '#description' => $this->t('Time of inactivity after which the failure count resets.'),
            '#default_value' => $config->get('reset_window') ?? 3600,
            '#min' => 60,
            '#required' => TRUE,
        ];

        $form['permanent_block_at'] = [
            '#type' => 'number',
            '#title' => $this->t('Permanent block after (failures)'),
            '#description' => $this->t('Number of failed attempts at which the user account will be permanently blocked (admin must unblock). Set 0 to disable account blocking.'),
            '#default_value' => $config->get('permanent_block_at') ?? 50,
            '#min' => 0,
            '#required' => TRUE,
        ];

        $form['flood_register_window'] = [
            '#type' => 'number',
            '#title' => $this->t('Flood register window (seconds)'),
            '#description' => $this->t('Window to register attempts with Drupal flood service (helps admin UIs).'),
            '#default_value' => $config->get('flood_register_window') ?? 3600,
            '#min' => 60,
            '#required' => TRUE,
        ];

        return parent::buildForm($form, $form_state) + $form;
    }

    public function submitForm(array &$form, FormStateInterface $form_state)
    {
        $this->configFactory->getEditable('login.settings')
            ->set('block_every', (int) $form_state->getValue('block_every'))
            ->set('base_delay', (int) $form_state->getValue('base_delay'))
            ->set('max_delay', (int) $form_state->getValue('max_delay'))
            ->set('reset_window', (int) $form_state->getValue('reset_window'))
            ->set('permanent_block_at', (int) $form_state->getValue('permanent_block_at'))
            ->set('flood_register_window', (int) $form_state->getValue('flood_register_window'))
            ->save();

        parent::submitForm($form, $form_state);
    }
}
