<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;

class HeaderValidator {
    private static function isValidDate(string $strDate): bool {
        if (!ctype_digit($strDate) || strlen($strDate) !== 8) {
            return false;
        }
        $year = substr($strDate, -4);
        $month = substr($strDate, -6, -4);
        $day = substr($strDate, 0, -6);
        $parsedDate = date_parse("$year-$month-$day");
        if ($parsedDate['error_count'] !== 0 || !checkdate($month, $day, $year)) {
            return false;
        }

        $providedDate = new \DateTime("$year-$month-$day");
        $currentDate = new \DateTime();

        return $providedDate < $currentDate;
    }

    public static function validate(Node $node, array &$errors, int $row_number): void {
        $provider_code      = $node->get('field_provider_code')->value;
        $branch_code        = $node->get('field_header_branch_code')->value;
        $reference_date     = $node->get('field_reference_date')->value;
        $version            = $node->get('field_version')->value ?? '1.0';
        $submission_type    = $node->get('field_submission_type')->value ?? '1';

        if (empty($provider_code) && empty($reference_date)) {
            $errors[] = "Row $row_number | 30-013: HEADER IS NOT PRESENT";
        }

        if (empty($provider_code)) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER CODE' IS MANDATORY";
        }
        if (strlen($provider_code) > 8) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER CODE' LENGTH MUST HAVE A LENGTH <= 8";
        }
        if (!empty($provider_code) && strtoupper(substr($provider_code, 0, 2)) !== 'CO') {
            $errors[] = "Row $row_number | FIELD 'PROVIDER CODE' IS NOT CORRECT";
        }

        if (!empty($branch_code) && strlen($branch_code) > 5) {
            $errors[] = "Row $row_number | FIELD 'BRANCH CODE' LENGTH MUST HAVE A LENGTH <= 5";
        }

        if (empty($reference_date)) {
            $errors[] = "Row $row_number | FIELD 'REFERENCE DATE' IS MANDATORY";
        }
        if (!empty($reference_date) && !self::isValidDate($reference_date)) {
            $errors[] = "Row $row_number | 30-007: FILE REFERENCE DATE IN THE HEADER/FOOTER IS NOT VALID OR IT IS GREATER THAN SYSDATE";
        }

        if (empty($version) || empty($submission_type) || $version !== '1.0' || $submission_type !== '1') {
            $errors[] = "Row $row_number | 30-015: FIELD 'VERSION' OR 'SUBMISSION TYPE' IS NOT VALID OR MISSING";
        }

    }
}
?>