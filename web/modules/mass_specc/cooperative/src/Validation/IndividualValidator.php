<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\IndividualDto;
use Drupal\cooperative\Repository\IndividualRepository;
use Drupal\cooperative\Repository\CompanyRepository;


class IndividualValidator {
    private IndividualRepository $individualRepository;
    private CompanyRepository $companyRepository;

    public function __construct(IndividualRepository $individualRepository, CompanyRepository $companyRepository) {
        $this->individualRepository = $individualRepository;
        $this->companyRepository = $companyRepository;
    }

    const COUNTRY_DOMAIN = DomainLists::COUNTRY_DOMAIN;

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

        $provided_date = new \DateTime("$year-$month-$day");
        $current_date = new \DateTime();

        return $provided_date < $current_date;
    }

    private static function isValidAge(string $strDate): bool {
        if (!self::isValidDate($strDate)) {
            return false;
        }
        $year = substr($strDate, -4);
        $month = substr($strDate, -6, -4);
        $day = substr($strDate, 0, -6);

        $birth_date = new \DateTime("$year-$month-$day");
        $current_date = new \DateTime();
        $age = $birth_date->diff($current_date)->y;

        return $age >= 18 && $age <= 100;
    }

    private static function isDateGreaterThan1900(string $strDate): bool {
        if (!self::isValidDate($strDate)) {
            return false;
        }
        $year = substr($strDate, -4);
        $month = substr($strDate, -6, -4);
        $day = substr($strDate, 0, -6);

        $reference_date = new \DateTime('1900-01-01');
        $input_date = new \DateTime("$year-$month-$day");

        return $input_date > $reference_date;
    }

    public function validate(IndividualDto $individualDto, array &$errors, int $row_number): void {
        $provider_code          = $individualDto->providerCode;
        $provider_subj_no       = $individualDto->providerSubjectNo;
        $branch_code            = $individualDto->branchCode;
        $title                  = $individualDto->title;
        $first_name             = $individualDto->firstName;
        $last_name              = $individualDto->lastName;
        $middle_name            = $individualDto->middleName;
        $suffix                 = $individualDto->suffix;
        $previous_last_name     = $individualDto->previousLastName;
        $gender                 = $individualDto->gender;
        $date_of_birth          = $individualDto->dateOfBirth;
        $place_of_birth         = $individualDto->placeOfBirth;
        $country_of_birth_code  = $individualDto->countryOfBirthCode;
        $nationality            = $individualDto->nationality;
        $resident               = $individualDto->resident;
        $civil_status           = $individualDto->civilStatus;
        $number_of_dependents   = $individualDto->numberOfDependents;
        $cars_owned             = $individualDto->carsOwned;

        $found_individual = $this->individualRepository->findByMandatoryFields($individualDto);
        $is_indiv_provider_subj_no_taken = $this->individualRepository
            ->isProviderSubjNoTakenInCoopOrBranch($provider_code, $provider_subj_no, $branch_code);
        $is_company_provider_subj_no_taken = $this->companyRepository
            ->isProviderSubjNoTakenInCoopOrBranch($provider_code, $provider_subj_no, $branch_code);

        if (($is_indiv_provider_subj_no_taken || $is_company_provider_subj_no_taken) && $found_individual === null) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-090: THE SAME 'PROVIDER SUBJECT NO' IS ALREADY ASSIGNED TO ANOTHER SUBJECT";
        }

        if (empty($provider_subj_no)) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER SUBJECT NO' IS MANDATORY";
        }
        if (strlen($provider_subj_no) > 38) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'PROVIDER SUBJECT NO' LENGTH MUST HAVE A LENGTH <= 38";
        }

        if (!ctype_digit($title) || $title < 10 || $title > 21) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'TITLE' IS NOT CORRECT";
        }

        if (empty($first_name)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-002: FIELD 'FIRST NAME' IS MANDATORY";
        }
        if (strlen($first_name) > 70) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'FIRST NAME' LENGTH MUST HAVE A LENGTH <= 70";
        }
        if (empty($last_name)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-003: FIELD 'LAST NAME' IS MANDATORY";
        }
        if (strlen($last_name) > 70) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'LAST NAME' LENGTH MUST HAVE A LENGTH <= 70";
        }
        if (strlen($middle_name) > 70) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'MIDDLE NAME' LENGTH MUST HAVE A LENGTH <= 70";
        }
        if (strlen($suffix) > 40) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'SUFFIX' LENGTH MUST HAVE A LENGTH <= 40";
        }
        if (strlen($previous_last_name) > 70) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'PREVIOUS LAST NAME' LENGTH MUST HAVE A LENGTH <= 70";
        }

        if (empty($gender)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-001: FIELD 'GENDER' IS MANDATORY";
        }
        if (!empty($gender) && strtoupper($gender) !== "M" && strtoupper($gender) !== "F") {
            $errors[] = "$provider_subj_no | Row $row_number | 10-004: FIELD 'GENDER' IS NOT CORRECT";
        }

        if (empty($date_of_birth)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-004: FIELD 'DATE OF BIRTH' IS MANDATORY";
        }
        if (!self::isValidDate($date_of_birth)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-005: FIELD 'DATE OF BIRTH' IS NOT CORRECT";
        }
        if (!self::isValidAge($date_of_birth)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-137: INDIVIDUAL AGE SHOULD BE BETWEEN 18 AND 100 YEARS";
        }   
        if (!self::isDateGreaterThan1900($date_of_birth)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-125: FIELD 'DATE OF BIRTH' MUST BE GREATER THAN 01-01-1900";
        }

        if (!empty($place_of_birth) && strlen($place_of_birth) > 100) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'PLACE OF BIRTH' LENGTH MUST HAVE A LENGTH <= 100";
        }

        if (!empty($country_of_birth_code) && !in_array(strtoupper($country_of_birth_code), self::COUNTRY_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-006: FIELD 'COUNTRY OF BIRTH' IS NOT CORRECT";
        }

        if (!empty($nationality) && !in_array(strtoupper($nationality), self::COUNTRY_DOMAIN)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-040: FIELD 'NATIONALITY' IS NOT CORRECT";
        }

        if (!empty($resident) && $resident !== '0' && $resident !== '1') {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'RESIDENT' IS NOT CORRECT";
        }

        if (!empty($civil_status) && !in_array($civil_status, ['1', '2', '3', '4'])) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-042: FIELD 'CIVIL STATUS' IS NOT CORRECT";
        }

        if (!empty($number_of_dependents) && !ctype_digit($number_of_dependents) || $number_of_dependents > 99) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'NUMBER OF DEPENDANTS' IS NOT CORRECT";
        }

        if (!empty($cars_owned) && !ctype_digit($cars_owned) || $cars_owned > 999) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CARS OWNED' IS NOT CORRECT";
        }
    }
}
