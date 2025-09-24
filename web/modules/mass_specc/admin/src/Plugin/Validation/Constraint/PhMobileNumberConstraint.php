<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that a field value is a valid PH mobile number.
 *
 * @Constraint(
 *   id = "PhMobileNumber",
 *   label = @Translation("Philippine mobile number", context = "Validation"),
 * )
 */
class PhMobileNumberConstraint extends Constraint {
  public $notValidMessage = 'The number %value is not a valid Philippine mobile number. It must start with 08 or 09.';
}
