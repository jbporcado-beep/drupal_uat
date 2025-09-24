<?php 
namespace Drupal\admin\Plugin\Validation\Constraint;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class AlphanumericConstraintValidator extends ConstraintValidator {
  public function validate($value, Constraint $constraint) {
    if ($value !== NULL && $value !== '' && !ctype_alnum($value)) {
      $this->context->buildViolation($constraint->message)
        ->setParameter('%value', $value)
        ->addViolation();
    }
  }
}
