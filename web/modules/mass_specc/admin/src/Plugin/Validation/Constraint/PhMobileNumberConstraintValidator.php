<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\ConstraintValidator;
use Drupal\Core\Form\FormStateInterface;

/**
 * Validates the PhMobileNumberConstraint.
 */
class PhMobileNumberConstraintValidator
{

  /**
   * {@inheritdoc}
   */
  public static function validate(&$element, FormStateInterface $form_state, &$complete_form)
  {
    $value = $element['#value'];

    if ($value === NULL || $value === '') {
      return;
    }

    if (!preg_match('/^(08|09)/', $value)) {
      $form_state->setError($element, t('Contact number must start with 08 or 09 and be 11 digits long.'));
    }
  }
}
