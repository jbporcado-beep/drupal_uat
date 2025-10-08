<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\IdentificationDto;

class IdentificationValidator {
    const COUNTRY_DOMAIN = DomainLists::COUNTRY_DOMAIN;
    const IDENTIFICATION_TYPE_DOMAIN = DomainLists::IDENTIFICATION_TYPE_DOMAIN;
    const ID_TYPE_DOMAIN = DomainLists::ID_TYPE_DOMAIN;

    private static function isValidDate(string $strDate): bool {
        if (strlen($strDate) !== 8) {
            return false;
        }

        $year = substr($strDate, -4);
        $month = substr($strDate, -6, -4);
        $day = substr($strDate, 0, -6);
        $parsedDate = date_parse("$year-$month-$day");
        if ($parsedDate['error_count'] !== 0 || !checkdate($month, $day, $year)) {
            return false;
        }

        $provided_date = new \DateTime("$year-$month-$day");
        $current_date = new \DateTime();

        return $provided_date < $current_date;
    }

    private static function isExpiryDateValid(string $strDate): bool {
        if (strlen($strDate) !== 8) {
            return false;
        }

        $year = substr($strDate, -4);
        $month = substr($strDate, -6, -4);
        $day = substr($strDate, 0, -6);
        $parsedDate = date_parse("$year-$month-$day");
        if ($parsedDate['error_count'] !== 0 || !checkdate($month, $day, $year)) {
            return false;
        }

        $provided_date = new \DateTime("$year-$month-$day");
        $current_date = new \DateTime();

        return $provided_date > $current_date;
    }

    public function validate(IdentificationDto $identificationDto, string $provider_subj_no, array &$errors, int $row_number, string $record_type): void {
        $identification_1_type        = $identificationDto->identification1Type;
        $identification_1_number      = $identificationDto->identification1Number;
        $identification_2_type        = $identificationDto->identification2Type;
        $identification_2_number      = $identificationDto->identification2Number;
        $id_1_type                    = $identificationDto->id1Type;
        $id_1_number                  = $identificationDto->id1Number;
        $id_1_issuedate               = $identificationDto->id1IssueDate;
        $id_1_issuecountry            = $identificationDto->id1IssueCountry;
        $id_1_expirydate              = $identificationDto->id1ExpiryDate;
        $id_1_issuedby                = $identificationDto->id1IssuedBy;
        $id_2_type                    = $identificationDto->id2Type;
        $id_2_number                  = $identificationDto->id2Number;
        $id_2_issuedate               = $identificationDto->id2IssueDate;
        $id_2_issuecountry            = $identificationDto->id2IssueCountry;
        $id_2_expirydate              = $identificationDto->id2ExpiryDate;
        $id_2_issuedby                = $identificationDto->id2IssuedBy;

        if (!empty($identification_1_type) 
            && !array_key_exists($identification_1_type, self::IDENTIFICATION_TYPE_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-009: FIELD 'IDENTIFICATION 1 TYPE' IS NOT CORRECT" ;
        }
        if (strlen($identification_1_number) > 20) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-043: FIELD 'IDENTIFICATION 1 NUMBER' LENGTH IS NOT CORRECT" ;
        }
        if ((!empty($identification_1_type) && empty($identification_1_number)) 
            || (empty($identification_1_type) && !empty($identification_1_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-069: FIELDS 'IDENTIFICATION 1 TYPE' AND 'IDENTIFICATION 1 NUMBER' " .
                        "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }
        if ($identification_1_type === '10' 
            && (!empty($identification_1_number) 
                && (strlen($identification_1_number) < 9 
                || strlen($identification_1_number) > 12 
                || !ctype_digit((string) $identification_1_number)))
            ) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-050: IF FIELD 'IDENTIFICATION 1 TYPE' IS 'TIN', " . 
                    "THE 'IDENTIFICATION 1 NUMBER' LENGTH MUST HAVE A LENGTH >= 9 AND <= 12, AND ONLY NUMBERS ARE ALLOWED";
        }

        if (!empty($identification_2_type) 
            && !array_key_exists($identification_2_type, self::IDENTIFICATION_TYPE_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-009: FIELD 'IDENTIFICATION 2 TYPE' IS NOT CORRECT" ;
        }
        if (strlen($identification_2_number) > 20) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-043: FIELD 'IDENTIFICATION 2 NUMBER' LENGTH IS NOT CORRECT" ;
        }
        if ((!empty($identification_2_type) && empty($identification_2_number)) 
            || (empty($identification_2_type) && !empty($identification_2_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-069: FIELDS 'IDENTIFICATION 2 TYPE' AND 'IDENTIFICATION 2 NUMBER' " .
                        "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }
        if ($identification_2_type === '10' 
            && (!empty($identification_1_number) 
                && (strlen($identification_2_number) < 9 
                || strlen($identification_2_number) > 12 
                || !ctype_digit((string) $identification_2_number)))
            ) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-050: IF FIELD 'IDENTIFICATION 2 TYPE' IS 'TIN', " .
                    "THE 'IDENTIFICATION 2 NUMBER' LENGTH MUST HAVE A LENGTH >= 9 AND <= 12, AND ONLY NUMBERS ARE ALLOWED";
        }
        
        if ($record_type === 'ID') {
            if ((!in_array($identification_1_type, ['10', '11', '12'])) && 
                (!in_array($identification_2_type, ['10', '11', '12']))) {
                $errors[] = "$provider_subj_no | Row $row_number | 20-102: AT LEAST ONE BETWEEN ALL FIELDS 'IDENTIFICATION TYPE' SHOULD HAVE " . 
                            "'TIN', 'SSS', OR 'GSIS' DOMAIN-VALUE";
            }
            if ($identification_1_type === '11' && 
            (strlen($identification_1_number) !== 10 || !ctype_digit((string) $identification_1_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-051: IF FIELD 'IDENTIFICATION 1 TYPE' IS 'SSS', " . 
                    "THE 'IDENTIFICATION 1 NUMBER' LENGTH MUST HAVE A LENGTH = 10, AND ONLY NUMBERS ARE ALLOWED";
            }
            if ($identification_1_type === '12' 
                && (strlen($identification_1_number) !== 10 
                    || strlen($identification_1_number) !== 11 
                    || !ctype_digit((string) $identification_1_number))
                ) {
                $errors[] = "$provider_subj_no | Row $row_number | 20-052: IF FIELD 'IDENTIFICATION 1 TYPE' IS 'GSIS', " . 
                        "THE 'IDENTIFICATION 1 NUMBER' LENGTH MUST HAVE A LENGTH = 10 OR 11, AND ONLY NUMBERS ARE ALLOWED";
            }
            if ($identification_2_type === '11' && 
            (strlen($identification_2_number) !== 10 || !ctype_digit((string) $identification_2_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-051: IF FIELD 'IDENTIFICATION 2 TYPE' IS 'SSS', " . 
                    "THE 'IDENTIFICATION 2 NUMBER' LENGTH MUST HAVE A LENGTH = 10, AND ONLY NUMBERS ARE ALLOWED";
            }
            if ($identification_2_type === '12' 
            && (strlen($identification_2_number) !== 10 
                || strlen($identification_2_number) !== 11 
                || !ctype_digit((string) $identification_2_number))
            ) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-052: IF FIELD 'IDENTIFICATION 2 TYPE' IS 'GSIS', " . 
                    "THE 'IDENTIFICATION 2 NUMBER' LENGTH MUST HAVE A LENGTH = 10 OR 11, AND ONLY NUMBERS ARE ALLOWED";
            }
        }
        else if ($record_type === 'BD') {
            if ($identification_1_type !== '10' && $identification_2_type !== '10') {
                $errors[] = "$provider_subj_no | Row $row_number | 20-102: AT LEAST ONE BETWEEN ALL FIELDS 'IDENTIFICATION TYPE' " . 
                            "SHOULD HAVE 'TIN' DOMAIN-VALUE";
            }
        }

        if (!empty($identification_1_type) && ($identification_1_type === $identification_2_type)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-067: MORE THAN ONE 'IDENTIFICATION TYPE' WITH THE SAME VALUE ARE NOT ALLOWED";
        }

        if (!empty($id_1_type) && !array_key_exists($id_1_type, self::ID_TYPE_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 1 TYPE' IS NOT CORRECT";
        }
        if (!empty($id_2_type) && !array_key_exists($id_2_type, self::ID_TYPE_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 2 TYPE' IS NOT CORRECT";
        }

        if ((!empty($id_1_type) && empty($id_1_number)) || (empty($id_1_type) && !empty($id_1_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELDS 'ID 1 TYPE' AND 'ID 1 NUMBER' MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }
        if ((!empty($id_2_type) && empty($id_2_number)) || (empty($id_2_type) && !empty($id_2_number))) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELDS 'ID 2 TYPE' AND 'ID 2 NUMBER' MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if (strlen($id_1_number) > 40) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 1 NUMBER' LENGTH MUST HAVE A LENGTH <= 40";
        }
        if (strlen($id_2_number) > 40) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 2 NUMBER' LENGTH MUST HAVE A LENGTH <= 40";
        }

        if (strlen($id_1_issuedby) > 250) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 1 ISSUED BY' LENGTH MUST HAVE A LENGTH <= 250";
        }
        if (strlen($id_2_issuedby) > 250) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ID 2 ISSUED BY' LENGTH MUST HAVE A LENGTH <= 250";
        }

        if (!empty($id_1_type) && ($id_1_type === $id_2_type)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-070: MORE THAN ONE 'ID TYPE' WITH THE SAME VALUE ARE NOT ALLOWED";
        }

        if (!empty($id_1_issuedate) && !self::isValidDate($id_1_issuedate)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-011: FIELD 'ID ISSUE DATE' IS NOT CORRECT";
        }
        if (!empty($id_2_issuedate) && !self::isValidDate($id_2_issuedate)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-011: FIELD 'ID ISSUE DATE' IS NOT CORRECT";
        }

        if (!empty($id_1_issuecountry) && !in_array($id_1_issuecountry, self::COUNTRY_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-012: FIELD 'ID 1 ISSUE COUNTRY' IS NOT CORRECT";
        }
        if (!empty($id_2_issuecountry) && !in_array($id_2_issuecountry, self::COUNTRY_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-012: FIELD 'ID 2 ISSUE COUNTRY' IS NOT CORRECT";
        }
        
        if (!empty($id_1_expirydate) && !self::isExpiryDateValid($id_1_expirydate)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-013: FIELD 'ID 1 EXPIRY DATE' IS NOT CORRECT";
        }
        if (!empty($id_2_expirydate) && !self::isExpiryDateValid($id_2_expirydate)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-013: FIELD 'ID 2 EXPIRY DATE' IS NOT CORRECT";
        }
    }
}
?>