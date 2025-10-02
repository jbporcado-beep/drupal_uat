<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\NonInstallmentContractDto;
use Drupal\cooperative\Repository\InstallmentContractRepository;
use Drupal\cooperative\Repository\NonInstallmentContractRepository;

class NonInstallmentContractValidator {
    private InstallmentContractRepository $installmentContractRepository;
    private NonInstallmentContractRepository $nonInstallmentContractRepository;

    public function __construct(
        InstallmentContractRepository $installmentContractRepository,
        NonInstallmentContractRepository $nonInstallmentContractRepository,
    ) {
        $this->installmentContractRepository = $installmentContractRepository;
        $this->nonInstallmentContractRepository = $nonInstallmentContractRepository;
    }

    const CONTRACT_PHASE_DOMAIN = DomainLists::CONTRACT_PHASE_DOMAIN;
    const PAYMENT_PERIODICITY_DOMAIN = DomainLists::PAYMENT_PERIODICITY_DOMAIN;
    const OVERDUE_DAYS_DOMAIN = DomainLists::OVERDUE_DAYS_DOMAIN;
    const CI_CONTRACT_TYPE_DOMAIN = DomainLists::CI_CONTRACT_TYPE_DOMAIN;
    const TRANSACTION_TYPE_DOMAIN = DomainLists::TRANSACTION_TYPE_DOMAIN;
    const CURRENCY_DOMAIN = DomainLists::CURRENCY_DOMAIN;

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
        return true;
    }

    private static function isDateGreaterThan(string $date_string1, string $date_string2): bool {
        $year1 = substr($date_string1, -4);
        $month1 = substr($date_string1, -6, -4);
        $day1 = substr($date_string1, 0, -6);

        $year2 = substr($date_string2, -4);
        $month2 = substr($date_string2, -6, -4);
        $day2 = substr($date_string2, 0, -6);

        $date1 = new \DateTime("$year1-$month1-$day1");
        $date2 = new \DateTime("$year2-$month2-$day2");

        return $date1 > $date2;
    }

    public function validate(NonInstallmentContractDto $dto, array &$errors, int $row_number): void {
        $subject                         = $dto->subject;
        $header                          = $dto->header;
        $provider_contract_no            = $dto->providerContractNo;
        $contract_end_actual_date        = $dto->contractEndActualDate;
        $contract_end_planned_date       = $dto->contractEndPlannedDate;
        $contract_phase                  = $dto->contractPhase;
        $contract_start_date             = $dto->contractStartDate;
        $contract_type                   = $dto->contractType;
        $credit_limit                    = $dto->creditLimit;
        $currency                        = $dto->currency;
        $original_currency               = $dto->originalCurrency;
        $outstanding_balance             = $dto->outstandingBalance;
        $overdue_payments_amount         = $dto->overduePaymentsAmount;
        $role                            = $dto->role;
        $transaction_type                = $dto->transactionType;

        $file_reference_date = $header?->get('field_reference_date')->value;
        $provider_code       = $header?->get('field_provider_code')->value;
        $branch_code         = $header?->get('field_branch_code')->value;

        $is_start_date_valid        = self::isValidDate($contract_start_date);
        $is_end_planned_date_valid  = self::isValidDate($contract_end_planned_date);
        $is_end_actual_date_valid   = self::isValidDate($contract_end_actual_date);


        $db_contract = $this->nonInstallmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);
        $db_contract_phase = $db_contract ? $db_contract->get('field_contract_phase')->value : null;
        $db_header         = $db_contract ? $db_contract->get('field_header')->entity : null;

        $db_file_reference_date = $db_header ? $db_header->get('field_reference_date')->value : null;

        $installment_contract = $this->installmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);

        if (!empty($db_contract) && in_array($db_contract_phase, ['CL', 'CA'])) {
            $errors[] = "Row $row_number | 10-302: CONTRACT IS ALREADY IN DATABASE WITH PHASE CLOSED OR CLOSED IN ADVANCE";
        }

        if ($installment_contract !== null) {
            $errors[] = "Row $row_number | 10-303: CONTRACT IS ALREADY IN DATABASE WITH A DIFFERENT CONTRACT CATEGORY";
        }

        if ($subject === null) {
            $errors[] = "Row $row_number | 10-307: CONTRACT IS NOT VALID BECAUSE AT LEAST ONE OF ITS LINKED SUBJECT DOESN'T EXIST";
        }

        if (!empty($db_file_reference_date) && $db_file_reference_date === $file_reference_date) {
            $errors[] = "Row $row_number | 20-310: MORE THAN ONE CONTRACT WITH THE SAME PROVIDER CONTRACT NUMBER " . 
            "AND REFERENCE DATE IS PRESENT";
        }

        if (!empty($db_file_reference_date) && self::isDateGreaterThan($db_file_reference_date, $file_reference_date)) {
            $errors[] = "Row $row_number | CONTRACT WITH MORE RECENT REFERENCE DATE IS ALREADY IN DATABASE";
        }

        if (empty($role)) {
            $errors[] = "Row $row_number | FIELD 'ROLE' IS MANDATORY";
        }
        if (!empty($role) && !in_array($role, ['B', 'G', 'C'])) {
            $errors[] = "Row $row_number | FIELD 'ROLE' IS NOT CORRECT";
        }

        if (empty($contract_phase)) {
            $errors[] = "Row $row_number | 10-024: FIELD 'CONTRACT PHASE' IS MANDATORY";
        }
        if (!empty($contract_phase) && !array_key_exists($contract_phase, self::CONTRACT_PHASE_DOMAIN)) {
            $errors[] = "Row $row_number | FIELD 'CONTRACT PHASE' IS NOT CORRECT";
        }

        if (empty($contract_type)) {
            $errors[] = "Row $row_number | FIELD 'CONTRACT TYPE' IS MANDATORY";
        }
        if (!empty($contract_type) && !array_key_exists($contract_type, self::CI_CONTRACT_TYPE_DOMAIN)) {
            $errors[] = "Row $row_number | FIELD 'CONTRACT TYPE' IS NOT CORRECT";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && empty($contract_start_date)) {
            $errors[] = "Row $row_number | 'CONTRACT PHASE' IN (AC,CL,CA) AND 'CONTRACT START DATE' IS EMPTY";
        }
        if (!$is_start_date_valid || ($is_start_date_valid && self::isDateGreaterThan($contract_start_date, $file_reference_date))) {
            $errors[] = "Row $row_number | 10-155: FIELD 'CONTRACT START DATE' IS NOT CORRECT";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && empty($contract_end_planned_date)) {
            $errors[] = "Row $row_number | 'CONTRACT PHASE' IN (AC,CL,CA) AND 'CONTRACT END PLANNED DATE' IS EMPTY";
        }
        if (!empty($contract_end_planned_date) && 
            (!$is_end_planned_date_valid || ($is_end_planned_date_valid && $is_start_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_planned_date)))) {
            $errors[] = "Row $row_number | 10-157: FIELD 'CONTRACT END PLANNED DATE' IS NOT CORRECT";
        }

        if (in_array($contract_phase, ['CL', 'CA']) && empty($contract_end_actual_date)) {
            $errors[] = "Row $row_number | 10-253: 'CONTRACT PHASE' IN (CL,CA) AND 'CONTRACT END ACTUAL DATE' IS EMPTY";
        }
        if (!empty($contract_end_actual_date) && !$is_end_actual_date_valid || ($is_end_actual_date_valid && $is_start_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_actual_date))) {
            $errors[] = "Row $row_number | 10-158: FIELD 'CONTRACT END ACTUAL DATE' IS NOT CORRECT";
        }

        if (!ctype_digit($outstanding_balance) || strlen($outstanding_balance) > 15) {
            $errors[] = "Row $row_number | 10-169: FIELD 'OUTSTANDING BALANCE' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }
        if (in_array($contract_phase, ['CL', 'CA']) && !empty($outstanding_balance)) {
            $errors[] = "Row $row_number | 'CONTRACT PHASE' IN (CL,CA) AND 'OUTSTANDING BALANCE' IS NOT EMPTY";
        }

        if ($is_start_date_valid && $is_end_planned_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_planned_date)) {
            $errors[] = "Row $row_number | 10-255: 'CONTRACT START DATE' IS GREATER THAN 'CONTRACT END PLANNED DATE'";
        }

        if ($is_start_date_valid && $is_end_actual_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_actual_date)) {
            $errors[] = "Row $row_number | 10-256:'CONTRACT START DATE' IS GREATER THAN 'CONTRACT END ACTUAL DATE'";
        }

        if ($contract_phase === 'AC' && empty($outstanding_balance) &&
            $is_end_planned_date_valid && self::isDateGreaterThan($contract_end_planned_date, $file_reference_date)) {
            $errors[] = "Row $row_number | 10-272: 'CONTRACT PHASE' IN (AC) AND 'OUTSTANDING BALANCE' IS EMPTY AND " . 
            "'CONTRACT REFERENCE DATE' IS LESS THAN 'CONTRACT END PLANNED DATE'";
        }

        if ($is_start_date_valid && self::isDateGreaterThan($contract_start_date, $file_reference_date)) {
            $errors[] = "Row $row_number | 10-290: 'CONTRACT REFERENCE DATE' IS LESS THAN 'CONTRACT START DATE'";
        }

        if (empty($transaction_type)) {
            $errors[] = "Row $row_number | 20-065: FIELD 'TYPE OF TRANSACTION' IS MANDATORY";
        }
        if (!empty($transaction_type) && !array_key_exists($transaction_type, self::TRANSACTION_TYPE_DOMAIN)) {
            $errors[] = "Row $row_number | 20-061: FIELD 'TYPE OF TRANSACTION ' IS NOT CORRECT";
        }

        if (empty($currency)) {
            $errors[] = "Row $row_number | FIELD 'CURRENCY' IS MANDATORY";
        }
        if (!empty($currency) && $currency !== 'PHP') {
            $errors[] = "Row $row_number | 20-103: FIELD 'CURRENCY' MUST HAVE PHP DOMAIN-VALUE";
        }

        if (empty($original_currency)) {
            $errors[] = "Row $row_number | FIELD 'ORIGINAL CURRENCY' IS MANDATORY";
        }
        if (!empty($original_currency) && !in_array($original_currency, self::CURRENCY_DOMAIN)) {
            $errors[] = "Row $row_number | FIELD 'ORIGINAL CURRENCY' IS NOT CORRECT";
        }

        if ((int) $outstanding_balance < 0) {
            $errors[] = "Row $row_number | 20-205: FIELD 'OUTSTANDING BALANCE' CAN'T HAVE A NEGATIVE VALUE";
        }
    }
}
?>