<?php
namespace Drupal\admin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;

/**
 * Checks that the value is alphanumeric.
 *
 * @Constraint(
 *   id = "Alphanumeric",
 *   label = @Translation("Alphanumeric", context = "Validation"),
 * )
 */
class AlphanumericConstraint extends Constraint {
  public $message = 'The value %value is not alphanumeric.';
}
