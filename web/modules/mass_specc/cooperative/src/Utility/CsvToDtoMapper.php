<?php

namespace Drupal\cooperative\Utility;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\IndividualDto;
use Drupal\cooperative\Dto\HeaderDto;
use Drupal\cooperative\Dto\CompanyDto;
use Drupal\cooperative\Dto\FamilyDto;
use Drupal\cooperative\Dto\AddressDto;
use Drupal\cooperative\Dto\IdentificationDto;
use Drupal\cooperative\Dto\ContactDto;
use Drupal\cooperative\Dto\EmploymentDto;
use Drupal\cooperative\Dto\InstallmentContractDto;
use Drupal\cooperative\Dto\NonInstallmentContractDto;

class CsvToDtoMapper {

    public static function mapToHeaderDto(array $row): HeaderDto {
        return new HeaderDto(
            providerCode:   $row['provider code'] ?? '',
            branchCode:     $row['branch code'] ?? '',
            referenceDate:  $row['reference date'] ?? '',
            version:        $row['version'] ?? '1.0',
            submissionType: $row['submission type'] ?? '1'
        );
    }

    public static function mapToIndividualDto(array $row): IndividualDto {

        $familyDto = self::mapToFamilyDto($row);
        $addressDto = self::mapToAddressDto($row);
        $identificationDto = self::mapToIdentificationDto($row);
        $contactDto = self::mapToContactDto($row);
        $employmentDto = self::mapToEmploymentDto($row);

        $date_of_birth = self::addLeadingZeroToDate($row['date of birth'] ?? '');

        return new IndividualDto(
            family:              $familyDto,
            address:             $addressDto,
            identification:      $identificationDto,
            contact:             $contactDto,
            employment:          $employmentDto,

            providerSubjectNo:   $row['provider subject no'] ?? '',
            providerCode:        $row['provider code'] ?? '',
            branchCode:          $row['branch code'] ?? '',
            title:               $row['title'] ?? '',
            firstName:           $row['first name'] ?? '',
            lastName:            $row['last name'] ?? '',
            middleName:          $row['middle name'] ?? '',
            suffix:              $row['suffix'] ?? '',
            previousLastName:    $row['previous last name'] ?? '',
            gender:              $row['gender'] ?? '',
            dateOfBirth:         $date_of_birth,
            placeOfBirth:        $row['place of birth'] ?? '',
            countryOfBirthCode:  $row['country of birth code'] ?? '',
            nationality:         $row['nationality'] ?? '',
            resident:            $row['resident'] ?? '',
            civilStatus:         $row['civil status'] ?? '',
            numberOfDependents:  $row['number of dependents'] ?? '',
            carsOwned:           $row['cars owned'] ?? ''
        );
    }

    public static function mapToCompanyDto(array $row): CompanyDto {

        $addressDto = self::mapToAddressDto($row);
        $identificationDto = self::mapToIdentificationDto($row);
        $contactDto = self::mapToContactDto($row);

        return new CompanyDto(
            address:             $addressDto,
            identification:      $identificationDto,
            contact:             $contactDto,

            providerSubjectNo:   $row['provider subject no'] ?? '',
            providerCode:        $row['provider code'] ?? '',
            branchCode:          $row['branch code'] ?? '',
            tradeName:           $row['trade name'] ?? '',
        );
    }

    public static function mapToInstallmentContractDto(array $row, ?Node $header, ?Node $subject): InstallmentContractDto {
        $contract_end_actual_date  = self::addLeadingZeroToDate($row['contract end actual date'] ?? '');
        $contract_end_planned_date = self::addLeadingZeroToDate($row['contract end planned date'] ?? '');
        $contract_start_date       = self::addLeadingZeroToDate($row['contract start date'] ?? '');
        $next_payment_date         = self::addLeadingZeroToDate($row['next payment date'] ?? '');

        return new InstallmentContractDto(
            header:                    $header,
            subject:                   $subject,
            providerContractNo:        $row['provider contract no'] ?? '',
            contractEndActualDate:     $contract_end_actual_date,
            contractEndPlannedDate:    $contract_end_planned_date,
            contractPhase:             $row['contract phase'] ?? '',
            contractStartDate:         $contract_start_date,
            contractType:              $row['contract type'] ?? '',
            currency:                  $row['currency'] ?? '',
            financedAmount:            $row['financed amount'] ?? '',
            installmentsNumber:        $row['installments number'] ?? '',
            lastPaymentAmount:         $row['last payment amount'] ?? '',
            monthlyPaymentAmount:      $row['monthly payment amount'] ?? '',
            nextPaymentDate:           $next_payment_date,
            originalCurrency:          $row['original currency'] ?? '',
            outstandingBalance:        $row['outstanding balance'] ?? '',
            outstandingPaymentsNumber: $row['outstanding payments number'] ?? '',
            overdueDays:               $row['overdue days'] ?? '',
            overduePaymentsAmount:     $row['overdue payments amount'] ?? '',
            overduePaymentsNumber:     $row['overdue payments number'] ?? '',
            paymentPeriodicity:        $row['payment periodicity'] ?? '',
            role:                      $row['role'] ?? '',
            transactionType:           $row['transaction type sub facility'] ?? '',
        );
    }

    public static function mapToNonInstallmentContractDto(array $row, ?Node $header, ?Node $subject): NonInstallmentContractDto {
        $contract_end_actual_date  = self::addLeadingZeroToDate($row['contract end actual date'] ?? '');
        $contract_end_planned_date = self::addLeadingZeroToDate($row['contract end planned date'] ?? '');
        $contract_start_date       = self::addLeadingZeroToDate($row['contract start date'] ?? '');

        return new NonInstallmentContractDto(
            header:                    $header,
            subject:                   $subject,
            providerContractNo:        $row['provider contract no'] ?? '',
            contractEndActualDate:     $contract_end_actual_date,
            contractEndPlannedDate:    $contract_end_planned_date,
            contractPhase:             $row['contract phase'] ?? '',
            contractStartDate:         $contract_start_date,
            contractType:              $row['contract type'] ?? '',
            creditLimit:               $row['credit limit'] ?? '',
            currency:                  $row['currency'] ?? '',
            originalCurrency:          $row['original currency'] ?? '',
            outstandingBalance:        $row['outstanding balance'] ?? '',
            overduePaymentsAmount:     $row['overdue payments amount'] ?? '',
            role:                      $row['role'] ?? '',
            transactionType:           $row['transaction type sub facility'] ?? '',
        );
    }

    private static function addLeadingZeroToDate(string $date): string {
        return strlen($date) === 7 ? '0' . $date : $date;
    }

    private static function mapToFamilyDto(array $row): FamilyDto {
        return new FamilyDto(
            spouseFirstName:      $row['spouse first name'] ?? '',
            spouseLastName:       $row['spouse last name'] ?? '',
            spouseMiddleName:     $row['spouse middle name'] ?? '',
            motherMaidenFullName: $row['mother maiden full name'] ?? '',
            fatherFirstName:      $row['father first name'] ?? '',
            fatherLastName:       $row['father last name'] ?? '',
            fatherMiddleName:     $row['father middle name'] ?? '',
            fatherSuffix:         $row['father suffix'] ?? '',
        );      
    }

    private static function mapToAddressDto(array $row): AddressDto {
        return new AddressDto(
            address1Type:        $row['address 1 address type'] ?? '',
            address1FullAddress: $row['address 1 fulladdress'] ?? '',
            address2Type:        $row['address 2 address type'] ?? '',
            address2FullAddress: $row['address 2 fulladdress'] ?? '',
        );
    }

    private static function mapToIdentificationDto(array $row): IdentificationDto {
        $identification1_number = !empty($row['identification 1 number']) ? (int) $row['identification 1 number'] : '';
        $identification2_number = !empty($row['identification 2 number']) ? (int) $row['identification 2 number'] : '';
        $id1_number = !empty($row['id 1 number']) ? (int) $row['id 1 number'] : '';
        $id2_number = !empty($row['id 2 number']) ? (int) $row['id 2 number'] : '';
        return new IdentificationDto(
            identification1Type:         $row['identification 1 type'] ?? '',
            identification1Number:       $identification1_number,
            identification2Type:         $row['identification 2 type'] ?? '',
            identification2Number:       $identification2_number,
            id1Type:                     $row['id 1 type'] ?? '',
            id1Number:                   $id1_number,
            id1IssueDate:                $row['id 1 issuedate'] ?? '',
            id1IssueCountry:             $row['id 1 issuecountry'] ?? '',
            id1ExpiryDate:               $row['id 1 expirydate'] ?? '',
            id1IssuedBy:                 $row['id 1 issued by'] ?? '',
            id2Type:                     $row['id 2 type'] ?? '',
            id2Number:                   $id2_number,
            id2IssueDate:                $row['id 2 issuedate'] ?? '',
            id2IssueCountry:             $row['id 2 issuecountry'] ?? '',
            id2ExpiryDate:               $row['id 2 expirydate'] ?? '',
            id2IssuedBy:                 $row['id 2 issued by'] ?? '',
        );
    }

    private static function mapToContactDto(array $row): ContactDto {
        $contact_1_type  = $row['contact 1 type'] ?? '';
        $contact_2_type  = $row['contact 2 type'] ?? '';
        $contact1_value = ($contact_1_type >= 1 && $contact_1_type <= 6)
            ? (isset($row['contact 1 value']) ? (int) $row['contact 1 value'] : '')
            : ($row['contact 1 value'] ?? '');
        $contact2_value = ($contact_2_type >= 1 && $contact_2_type <= 6)
            ? (isset($row['contact 2 value']) ? (int) $row['contact 2 value'] : '')
            : ($row['contact 2 value'] ?? '');

        return new ContactDto(
            contact1Type:  $contact_1_type,
            contact1Value: $contact1_value,
            contact2Type:  $contact_2_type,
            contact2Value: $contact2_value,
        );
    }

    private static function mapToEmploymentDto(array $row): EmploymentDto {
        return new EmploymentDto(
            tradeName:        $row['employment trade name'] ?? '',
            psic:             $row['employment psic'] ?? '',
            occupationStatus: $row['employment occupationstatus'] ?? '',
            occupation:       $row['employment occupation'] ?? '',
        );
    }
}
?>