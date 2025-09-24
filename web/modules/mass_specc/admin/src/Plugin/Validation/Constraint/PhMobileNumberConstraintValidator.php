<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

/**
 * Validates the PhMobileNumberConstraint.
 */
class PhMobileNumberConstraintValidator extends ConstraintValidator {

  /**
   * {@inheritdoc}
   */
  public function validate($value, Constraint $constraint) {
    if ($value === NULL || $value === '') {
      return;
    }

    if (!preg_match('/^(08|09)/', $value)) {
      $this->context->addViolation($constraint->notValidMessage, ['%value' => $value]);
    }
  }

}
