<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\FamilyDto;

class FamilyValidator {
    private static function normalizeCamelCase(string $camelCaseString): string {
        $normalizedString = preg_replace('/([a-z])([A-Z])/', '$1 $2', $camelCaseString);
        return $normalizedString;
    }

    public function validate(FamilyDto $familyDto, array &$errors, int $row_number): void {
        $fields = get_object_vars($familyDto);
        foreach ($fields as $field_name => $field_value) {
            if (in_array($field_name, [
                'spouseFirstName',
                'spouseLastName',
                'spouseMiddleName',
                'motherMaidenFullName',
                'fatherFirstName',
                'fatherLastName',
                'fatherMiddleName',
            ]) && strlen($field_value) > 70) {
                $normalized_field_name = strtoupper(self::normalizeCamelCase($field_name));
                $errors[] = "Row $row_number | FIELD '$normalized_field_name' LENGTH MUST HAVE A LENGTH <= 70";
            }
            else if ($field_name === 'fatherSuffix' && strlen($field_value) > 40) {
                $errors[] = "Row $row_number | FIELD 'FATHER SUFFIX' LENGTH MUST HAVE A LENGTH <= 40";
            }
        }
    }
}
?>