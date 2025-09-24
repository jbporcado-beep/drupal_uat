<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Drupal\Core\Form\FormStateInterface;

class EmailConstraintValidator {

  /**
   * Validates an email field.
   */
  public static function validate(&$element, FormStateInterface $form_state, &$complete_form) {
    $email = $element['#value'];

    if (!$email) {
      return; 
    }

    if (!str_contains($email, '@')) {
      $form_state->setError($element, t('Email must contain @ symbol.'));
      return;
    }

    [$local, $domain] = explode('@', $email, 2);

    if (strlen($local) > 64) {
      $form_state->setError($element, t('The local part of the email (before @) cannot exceed 64 characters.'));
    }

    if (strlen($domain) > 255) {
      $form_state->setError($element, t('The domain part of the email cannot exceed 255 characters.'));
    }

    if (strlen($email) > 320) {
      $form_state->setError($element, t('The total length of the email cannot exceed 320 characters.'));
    }

    if (!preg_match('/^(?!.*\.\.)[A-Za-z0-9._%-]+$/', $local)) {
      $form_state->setError($element, t('Invalid characters in the local part of the email.'));
    }

    if (!preg_match('/^[A-Za-z0-9.-]+\.[A-Za-z]{2,}$/', $domain)) {
      $form_state->setError($element, t('The domain part of the email must be valid and contain at least one dot.'));
    }
  }
}
