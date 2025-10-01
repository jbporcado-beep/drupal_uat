<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;

use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\EmploymentDto;

class EmploymentValidator {
    const PSIC_DOMAIN = DomainLists::PSIC_DOMAIN;
    const PSOC_DOMAIN = DomainLists::PSOC_DOMAIN;

    public function validate(EmploymentDto $employmentDto, array &$errors, int $row_number): void {
        $employment_trade_name        = $employmentDto->tradeName;
        $employment_psic              = $employmentDto->psic;
        $employment_occupation_status = $employmentDto->occupationStatus;
        $employment_occupation        = $employmentDto->occupation;

        if (strlen($employment_trade_name) > 120) {
            $errors[] = "Row $row_number | FIELD 'EMPLOYMENT TRADE NAME' LENGTH MUST HAVE A LENGTH <= 120"; 
        }

        if (!empty($employment_psic) && !in_array($employment_psic, self::PSIC_DOMAIN)) {
            $errors[] = "Row $row_number | 20-045: FIELD 'PSIC' IS NOT CORRECT"; 
        }

        if (!empty($employment_occupation_status) && ($employment_occupation_status < 1 || $employment_occupation_status > 9)) {
            $errors[] = "Row $row_number | FIELD 'EMPLOYMENT OCCUPATION STATUS' IS NOT CORRECT"; 
        }

        if (!empty($employment_occupation) && !in_array($employment_occupation, self::PSOC_DOMAIN)) {
            $errors[] = "Row $row_number | FIELD 'EMPLOYMENT OCCUPATION' IS NOT CORRECT";
        }

    }
}
?>