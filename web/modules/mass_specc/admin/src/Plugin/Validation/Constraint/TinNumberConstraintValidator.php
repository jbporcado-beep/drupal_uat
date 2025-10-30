<?php

namespace Drupal\admin\Plugin\Validation\Constraint;

use Drupal\Core\Form\FormStateInterface;

class TinNumberConstraintValidator
{
    public static function validate(&$element, FormStateInterface $form_state, &$complete_form)
    {
        $value = $element['#value'];

        if ($value === NULL || $value === '') {
            return;
        }

        $value = trim($value);

        if (!ctype_digit($value)) {
            $form_state->setError($element, t('TIN Number must contain only numeric digits (0-9).'));
            return;
        }

        $length = strlen($value);

        if ($length < 9 || $length > 13) {
            $form_state->setError($element, t('TIN Number must be between 9 and 13 digits long.'));
        }
    }
}