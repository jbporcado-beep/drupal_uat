<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\InstallmentContractDto;
use Drupal\cooperative\Repository\InstallmentContractRepository;
use Drupal\cooperative\Repository\NonInstallmentContractRepository;

class InstallmentContractValidator {
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
        if (!self::isValidDate($date_string1) || !self::isValidDate($date_string2)) {
            return false;
        }
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

    public function validate(InstallmentContractDto $dto, array &$errors, int $row_number): void {
        $subject                         = $dto->subject;
        $header                          = $dto->header;
        $provider_contract_no            = $dto->providerContractNo;
        $contract_end_actual_date        = $dto->contractEndActualDate;
        $contract_end_planned_date       = $dto->contractEndPlannedDate;
        $contract_phase                  = $dto->contractPhase;
        $contract_start_date             = $dto->contractStartDate;
        $contract_type                   = $dto->contractType;
        $currency                        = $dto->currency;
        $financed_amount                 = $dto->financedAmount;
        $installments_number             = $dto->installmentsNumber;
        $last_payment_amount             = $dto->lastPaymentAmount;
        $monthly_payment_amount          = $dto->monthlyPaymentAmount;
        $next_payment_date               = $dto->nextPaymentDate;
        $original_currency               = $dto->originalCurrency;
        $outstanding_balance             = $dto->outstandingBalance;
        $outstanding_payments_number     = $dto->outstandingPaymentsNumber;
        $overdue_days                    = $dto->overdueDays;
        $overdue_payments_amount         = $dto->overduePaymentsAmount;
        $overdue_payments_number         = $dto->overduePaymentsNumber;
        $payment_periodicity             = $dto->paymentPeriodicity;
        $role                            = $dto->role;
        $transaction_type                = $dto->transactionType;

        $file_reference_date = $header?->get('field_reference_date')->value ?? '';
        $provider_code       = $header?->get('field_provider_code')->value ?? '';
        $branch_code         = $header?->get('field_branch_code')->value ?? '';

        $is_start_date_valid        = self::isValidDate($contract_start_date);
        $is_end_planned_date_valid  = self::isValidDate($contract_end_planned_date);
        $is_end_actual_date_valid   = self::isValidDate($contract_end_actual_date);
        $is_next_payment_date_valid = self::isValidDate($next_payment_date);

        $db_contract = $this->installmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);

        $db_contract_phase = $db_contract ? $db_contract->get('field_contract_phase')->value : '';
        $db_header         = $db_contract ? $db_contract->get('field_header')->entity : null;

        $db_file_reference_date = $db_header ? $db_header->get('field_reference_date')->value : '';


        $noninstallment_contract = $this->nonInstallmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);

        if (!empty($db_contract) && in_array($db_contract_phase, ['CL', 'CA'])) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-302: CONTRACT IS ALREADY IN DATABASE WITH PHASE CLOSED OR CLOSED IN ADVANCE";
        }

        if ($noninstallment_contract !== null) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-303: CONTRACT IS ALREADY IN DATABASE WITH A DIFFERENT CONTRACT CATEGORY";
        }

        if ($subject === null) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-307: CONTRACT IS NOT VALID BECAUSE AT LEAST ONE OF ITS LINKED SUBJECT DOESN'T EXIST";
        }

        if (empty($provider_contract_no)) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER CONTRACT NUMBER' IS MANDATORY";
        }

        if (!empty($db_file_reference_date) && $db_file_reference_date === $file_reference_date) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-310: MORE THAN ONE CONTRACT WITH THE SAME PROVIDER CONTRACT NUMBER " . 
            "AND REFERENCE DATE IS PRESENT";
        }

        if (!empty($db_file_reference_date) && self::isDateGreaterThan($db_file_reference_date, $file_reference_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | CONTRACT WITH MORE RECENT REFERENCE DATE IS ALREADY IN DATABASE";
        }

        if (empty($role)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'ROLE' IS MANDATORY";
        }
        if (!empty($role) && !in_array($role, ['B', 'G', 'C'])) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'ROLE' IS NOT CORRECT";
        }

        if (empty($contract_phase)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-024: FIELD 'CONTRACT PHASE' IS MANDATORY";
        }
        if (!empty($contract_phase) && !array_key_exists($contract_phase, self::CONTRACT_PHASE_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'CONTRACT PHASE' IS NOT CORRECT";
        }

        if (strlen($contract_type) === 0) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'CONTRACT TYPE' IS MANDATORY";
        }
        if (strlen($contract_type) !== 0 && !array_key_exists($contract_type, self::CI_CONTRACT_TYPE_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'CONTRACT TYPE' IS NOT CORRECT";
        }

        if (empty($payment_periodicity)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'PAYMENT PERIODICITY' IS MANDATORY";
        }
        if (!empty($payment_periodicity) && !in_array($payment_periodicity, self::PAYMENT_PERIODICITY_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-029: FIELD 'PAYMENT PERIODICITY' IS NOT CORRECT";
        }

        if (strlen($installments_number) === 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-030: FIELD 'INSTALLMENTS NUMBER' IS MANDATORY";
        }
        if (!ctype_digit($installments_number) || (int) $installments_number <= 0 || strlen($installments_number) > 3) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-059: FIELD 'INSTALLMENTS NUMBER' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }

        if (strlen($financed_amount) === 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-161: FIELD 'FINANCED AMOUNT' IS MANDATORY";
        }
        if (!ctype_digit($financed_amount) || (int) $financed_amount <= 0 || strlen($financed_amount) > 15) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-058: FIELD 'FINANCED AMOUNT' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }

        if (!empty($monthly_payment_amount) && 
            (!ctype_digit($monthly_payment_amount) || (int) $monthly_payment_amount <= 0 || strlen($monthly_payment_amount) > 15)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-060: FIELD 'MONTHLY PAYMENT AMOUNT' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }
        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && (empty($monthly_payment_amount) || (int) $monthly_payment_amount === 0)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-268: 'CONTRACT PHASE' IN (AC,CL,CA) AND 'MONTHLY PAYMENT AMOUNT' IS EMPTY OR 0";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && empty($contract_start_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (AC,CL,CA) AND 'CONTRACT START DATE' IS EMPTY";
        }
        if ($is_start_date_valid && $is_next_payment_date_valid && self::isDateGreaterThan($contract_start_date, $next_payment_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-262: 'CONTRACT START DATE' IS GREATER THAN 'NEXT PAYMENT DATE'";
        }
        if (!$is_start_date_valid || ($is_start_date_valid && self::isDateGreaterThan($contract_start_date, $file_reference_date))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-155: FIELD 'CONTRACT START DATE' IS NOT CORRECT";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && empty($contract_end_planned_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (AC,CL,CA) AND 'CONTRACT END PLANNED DATE' IS EMPTY";
        }
        if (!empty($contract_end_planned_date) && 
            (!$is_end_planned_date_valid || ($is_end_planned_date_valid && $is_start_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_planned_date)))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-157: FIELD 'CONTRACT END PLANNED DATE' IS NOT CORRECT";
        }

        if (in_array($contract_phase, ['CL', 'CA']) && empty($contract_end_actual_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-253: 'CONTRACT PHASE' IN (CL,CA) AND 'CONTRACT END ACTUAL DATE' IS EMPTY";
        }
        if (!empty($contract_end_actual_date) && !$is_end_actual_date_valid || ($is_end_actual_date_valid && $is_start_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_actual_date))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-158: FIELD 'CONTRACT END ACTUAL DATE' IS NOT CORRECT";
        }

        $are_all_dates_valid = $is_start_date_valid && $is_end_actual_date_valid && $is_end_planned_date_valid && $is_next_payment_date_valid;
        if (!empty($next_payment_date) && !$is_next_payment_date_valid || ($are_all_dates_valid && 
            (self::isDateGreaterThan($contract_start_date, $next_payment_date) ||
            self::isDateGreaterThan($next_payment_date, $contract_end_planned_date) || 
            self::isDateGreaterThan($next_payment_date, $contract_end_actual_date)))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-166: FIELD 'NEXT PAYMENT DATE' IS NOT CORRECT";
        }

        if (strlen($outstanding_payments_number) !== 0 && 
            (!ctype_digit($outstanding_payments_number) || strlen($outstanding_payments_number) > 3)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-168: FIELD 'OUTSTANDING PAYMENTS NUMBER' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }
        if (in_array($contract_phase, ['CL', 'CA']) && !empty($outstanding_payments_number)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (CL,CA) AND 'OUTSTANDING PAYMENTS NUMBER' IS NOT EMPTY";
        }

        if (!empty($outstanding_balance) && (!ctype_digit($outstanding_balance) || strlen($outstanding_balance) > 15)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-169: FIELD 'OUTSTANDING BALANCE' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }
        if (in_array($contract_phase, ['CL', 'CA']) && !empty($outstanding_balance)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (CL,CA) AND 'OUTSTANDING BALANCE' IS NOT EMPTY";
        }

        if (!empty($outstanding_payments_number) && empty($outstanding_balance) ||
            empty($outstanding_payments_number) && !empty($outstanding_balance)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELDS 'OUTSTANDING PAYMENTS NUMBER' AND 'OUTSTANDING BALANCE' " . 
            "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if (!empty($overdue_payments_amount) && (!ctype_digit($overdue_payments_amount) || strlen($overdue_payments_amount) > 15)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-171: FIELD 'OVERDUE PAYMENTS AMOUNT' IS NOT NUMERIC OR LENGTH IS NOT CORRECT";
        }
        if (in_array($contract_phase, ['RQ', 'RN', 'RF']) && !empty($overdue_payments_number)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (RE,RN,RQ) AND 'OVERDUE PAYMENTS NUMBER' IS NOT EMPTY";
        }
        if (in_array($contract_phase, ['RQ', 'RN', 'RF']) && !empty($overdue_payments_amount)) {
            $errors[] = "$provider_contract_no | Row $row_number | 'CONTRACT PHASE' IN (RE,RN,RQ) AND 'OVERDUE PAYMENTS AMOUNT' IS NOT EMPTY";
        }

        if (!empty($overdue_payments_number) && empty($overdue_payments_amount) ||
            empty($overdue_payments_number) && !empty($overdue_payments_amount)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELDS 'OVERDUE PAYMENTS NUMBER' AND 'OVERDUE PAYMENTS AMOUNT' " . 
            "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && strlen($overdue_days) === 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-090: IF 'CONTRACT PHASE' IN (AC,CL,CA), 'OVERDUE DAYS' IS MANDATORY";
        }
        if (!empty($overdue_days) && !in_array($overdue_days, self::OVERDUE_DAYS_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-172: FIELD 'OVERDUE DAYS' IS NOT CORRECT";
        }

        if ($is_start_date_valid && $is_end_planned_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_planned_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-255: 'CONTRACT START DATE' IS GREATER THAN 'CONTRACT END PLANNED DATE'";
        }

        if ($is_start_date_valid && $is_end_actual_date_valid && 
            self::isDateGreaterThan($contract_start_date, $contract_end_actual_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-256:'CONTRACT START DATE' IS GREATER THAN 'CONTRACT END ACTUAL DATE'";
        }

        if (in_array($contract_phase, ['CL', 'CA']) && !empty($next_payment_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-259: 'CONTRACT PHASE' IN (CL,CA) AND 'NEXT PAYMENT DATE' IS NOT EMPTY";
        }

        if ($is_end_actual_date_valid && $is_next_payment_date_valid && 
            self::isDateGreaterThan($next_payment_date, $contract_end_actual_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-260: 'CONTRACT END ACTUAL DATE' IS LESS THAN 'NEXT PAYMENT DATE'";
        }

        if ($is_end_planned_date_valid && $is_next_payment_date_valid && 
            self::isDateGreaterThan($next_payment_date, $contract_end_planned_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-261: 'CONTRACT END PLANNED DATE' IS LESS THAN 'NEXT PAYMENT DATE'";
        }

        if ($is_start_date_valid && $is_next_payment_date_valid && 
            self::isDateGreaterThan($contract_start_date, $next_payment_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-262: 'CONTRACT START DATE' IS GREATER THAN 'NEXT PAYMENT DATE'";
        }

        if ($contract_phase === 'AC' && empty($outstanding_payments_number) &&
            $is_end_planned_date_valid && self::isDateGreaterThan($contract_end_planned_date, $file_reference_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-270: 'CONTRACT PHASE' IN (AC) AND 'OUTSTANDING PAYMENTS NUMBER' IS EMPTY " .
            "AND 'CONTRACT REFERENCE DATE' IS LESS THAN 'CONTRACT END PLANNED DATE'";
        }

        if ($contract_phase === 'AC' && empty($outstanding_balance) &&
            $is_end_planned_date_valid && self::isDateGreaterThan($contract_end_planned_date, $file_reference_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-272: 'CONTRACT PHASE' IN (AC) AND 'OUTSTANDING BALANCE' IS EMPTY AND " . 
            "'CONTRACT REFERENCE DATE' IS LESS THAN 'CONTRACT END PLANNED DATE'";
        }

        if ($contract_phase === 'AC' && 
            (((int) $outstanding_payments_number === 0 || empty($outstanding_payments_number)) && (int) $outstanding_balance > 0) ||
            ((int) $outstanding_payments_number > 0 && ((int) $outstanding_balance === 0 || empty($outstanding_balance)))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-274: 'CONTRACT PHASE' IN (AC) AND 'OUTSTANDING PAYMENTS NUMBER' = (0 OR EMPTY) " . 
            "AND 'OUTSTANDING BALANCE' > 0 OR 'OUTSTANDING PAYMENTS NUMBER' > 0 AND 'OUTSTANDING BALANCE' = (0 OR EMPTY)";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) &&
            (((int) $overdue_payments_number === 0 || empty($overdue_payments_number)) && (int) $overdue_payments_amount > 0) ||
            ((int) $overdue_payments_number > 0 && ((int) $overdue_payments_amount === 0 || empty($overdue_payments_amount)))) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-280: 'CONTRACT PHASE' IN (AC,CL,CA) AND 'OVERDUE PAYMENTS NUMBER' = (0 OR EMPTY) " . 
            "AND 'OVERDUE PAYMENTS AMOUNT' > 0 OR 'OVERDUE PAYMENTS NUMBER' > 0 AND 'OVERDUE PAYMENTS AMOUNT' = (0 OR EMPTY)";
        }

        if ($is_start_date_valid && self::isDateGreaterThan($contract_start_date, $file_reference_date)) {
            $errors[] = "$provider_contract_no | Row $row_number | 10-290: 'CONTRACT REFERENCE DATE' IS LESS THAN 'CONTRACT START DATE'";
        }

        if (empty($transaction_type)) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-065: FIELD 'TYPE OF TRANSACTION' IS MANDATORY";
        }
        if (!empty($transaction_type) && !array_key_exists($transaction_type, self::TRANSACTION_TYPE_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-061: FIELD 'TYPE OF TRANSACTION ' IS NOT CORRECT";
        }
        
        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && strlen($last_payment_amount) === 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-089: IF 'CONTRACT PHASE' IN (AC,CL,CA), 'LAST PAYMENT AMOUNT' IS MANDATORY";
        }
        if ((int) $last_payment_amount < 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-201: FIELD 'LAST PAYMENT AMOUNT' CAN'T HAVE A NEGATIVE VALUE";
        }

        if (empty($currency)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'CURRENCY' IS MANDATORY";
        }
        if (!empty($currency) && $currency !== 'PHP') {
            $errors[] = "$provider_contract_no | Row $row_number | 20-103: FIELD 'CURRENCY' MUST HAVE PHP DOMAIN-VALUE";
        }

        if (empty($original_currency)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'ORIGINAL CURRENCY' IS MANDATORY";
        }
        if (!empty($original_currency) && !in_array($original_currency, self::CURRENCY_DOMAIN)) {
            $errors[] = "$provider_contract_no | Row $row_number | FIELD 'ORIGINAL CURRENCY' IS NOT CORRECT";
        }

        if ((int) $outstanding_balance < 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-205: FIELD 'OUTSTANDING BALANCE' CAN'T HAVE A NEGATIVE VALUE";
        }

        if ((int) $overdue_payments_number < 0) {
            $errors[] = "$provider_contract_no | Row $row_number | 20-207: FIELD 'OVERDUE PAYMENTS NUMBER' CAN'T HAVE A NEGATIVE VALUE";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && 
            ctype_digit($outstanding_payments_number) && 
            ctype_digit($installments_number) &&
            $outstanding_payments_number > 0 &&
            (int) $outstanding_payments_number > (int) $installments_number){
            $errors[] = "$provider_contract_no | Row $row_number | 20-228: 'CONTRACT PHASE' IN (AC,CL,CA) AND 'OUTSTANDING PAYMENTS NUMBER' > 0 " . 
            "AND 'OUTSTANDING PAYMENTS NUMBER' > 'INSTALLMENTS NUMBER'";
        }

        if (in_array($contract_phase, ['AC', 'CL', 'CA']) && 
            ctype_digit($overdue_payments_number) && 
            ctype_digit($installments_number) &&
            $overdue_payments_number > 0 &&
            (int) $overdue_payments_number > (int) $installments_number){
            $errors[] = "$provider_contract_no | Row $row_number | 20-229: 'CONTRACT PHASE' IN (AC,CL,CA) AND 'OVERDUE PAYMENTS NUMBER' > 0 " . 
            "AND 'OVERDUE PAYMENTS NUMBER' > 'INSTALLMENTS NUMBER'";
        }
    }
}
?>