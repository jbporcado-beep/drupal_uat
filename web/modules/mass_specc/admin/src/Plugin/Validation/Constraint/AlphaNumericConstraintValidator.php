<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Drupal\Core\Form\FormStateInterface;

class AlphaNumericConstraintValidator
{

  /**
   * Form API validator to ensure value is alphanumeric plus allowed special chars and whitespace.
   */
  public static function validate(&$element, FormStateInterface $form_state, &$complete_form)
  {
    $value = $element['#value'];

    if (strlen($value) < 2) {
      $form_state->setError($element, t('The field should have at least 2 characters.'));
    }

    $pattern = '/^[a-zA-Z0-9\s!#$%&\'*+\-\/=?^_`{|}~\.]+$/';
    if ($value !== NULL && $value !== '' && !preg_match($pattern, $value)) {
      $form_state->setError($element, t('Only letters, numbers, spaces, and these special characters are allowed: ! # $ % & \' * + - / = ? ^ _ ` { | } ~ .'));
    }
  }
}
