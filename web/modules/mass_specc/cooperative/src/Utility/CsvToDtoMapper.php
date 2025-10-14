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
        $reference_date  = self::addLeadingZeroToDate($row['reference date'] ?? '');

        return new HeaderDto(
            providerCode:   $this->cleanSpaces($row['provider code'] ?? ''),
            branchCode:     $this->cleanSpaces($row['branch code'] ?? ''),
            referenceDate:  $this->cleanSpaces($reference_date),
            version:        $this->cleanSpaces($row['version'] ?? '1.0'),
            submissionType: $this->cleanSpaces($row['submission type'] ?? '1')
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

            providerSubjectNo:   $this->cleanSpaces($row['provider subject no'] ?? ''),
            providerCode:        $this->cleanSpaces($row['provider code'] ?? ''),
            branchCode:          $this->cleanSpaces($row['branch code'] ?? ''),
            title:               $this->cleanSpaces($row['title'] ?? ''),
            firstName:           $this->cleanSpaces($row['first name'] ?? ''),
            lastName:            $this->cleanSpaces($row['last name'] ?? ''),
            middleName:          $this->cleanSpaces($row['middle name'] ?? ''),
            suffix:              $this->cleanSpaces($row['suffix'] ?? ''),
            previousLastName:    $this->cleanSpaces($row['previous last name'] ?? ''),
            gender:              $this->cleanSpaces($row['gender'] ?? ''),
            dateOfBirth:         $this->cleanSpaces($date_of_birth),
            placeOfBirth:        $this->cleanSpaces($row['place of birth'] ?? ''),
            countryOfBirthCode:  $this->cleanSpaces($row['country of birth code'] ?? ''),
            nationality:         $this->cleanSpaces($row['nationality'] ?? ''),
            resident:            $this->cleanSpaces($row['resident'] ?? ''),
            civilStatus:         $this->cleanSpaces($row['civil status'] ?? ''),
            numberOfDependents:  $this->cleanSpaces($row['number of dependents'] ?? ''),
            carsOwned:           $this->cleanSpaces($row['cars owned'] ?? '')
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

            providerSubjectNo:   $this->cleanSpaces($row['provider subject no'] ?? ''),
            providerCode:        $this->cleanSpaces($row['provider code'] ?? ''),
            branchCode:          $this->cleanSpaces($row['branch code'] ?? ''),
            tradeName:           $this->cleanSpaces($row['trade name'] ?? ''),
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
            providerContractNo:        $this->cleanSpaces($row['provider contract no'] ?? ''),
            contractEndActualDate:     $this->cleanSpaces($contract_end_actual_date),
            contractEndPlannedDate:    $this->cleanSpaces($contract_end_planned_date),
            contractPhase:             $this->cleanSpaces($row['contract phase'] ?? ''),
            contractStartDate:         $this->cleanSpaces($contract_start_date),
            contractType:              $this->cleanSpaces($row['contract type'] ?? ''),
            currency:                  $this->cleanSpaces($row['currency'] ?? ''),
            financedAmount:            $this->cleanSpaces($row['financed amount'] ?? ''),
            installmentsNumber:        $this->cleanSpaces($row['installments number'] ?? ''),
            lastPaymentAmount:         $this->cleanSpaces($row['last payment amount'] ?? ''),
            monthlyPaymentAmount:      $this->cleanSpaces($row['monthly payment amount'] ?? ''),
            nextPaymentDate:           $this->cleanSpaces($next_payment_date),
            originalCurrency:          $this->cleanSpaces($row['original currency'] ?? ''),
            outstandingBalance:        $this->cleanSpaces($row['outstanding balance'] ?? ''),
            outstandingPaymentsNumber: $this->cleanSpaces($row['outstanding payments number'] ?? ''),
            overdueDays:               $this->cleanSpaces($row['overdue days'] ?? ''),
            overduePaymentsAmount:     $this->cleanSpaces($row['overdue payments amount'] ?? ''),
            overduePaymentsNumber:     $this->cleanSpaces($row['overdue payments number'] ?? ''),
            paymentPeriodicity:        $this->cleanSpaces($row['payment periodicity'] ?? ''),
            role:                      $this->cleanSpaces($row['role'] ?? ''),
            transactionType:           $this->cleanSpaces($row['transaction type sub facility'] ?? ''),
        );
    }

    public static function mapToNonInstallmentContractDto(array $row, ?Node $header, ?Node $subject): NonInstallmentContractDto {
        $contract_end_actual_date  = self::addLeadingZeroToDate($row['contract end actual date'] ?? '');
        $contract_end_planned_date = self::addLeadingZeroToDate($row['contract end planned date'] ?? '');
        $contract_start_date       = self::addLeadingZeroToDate($row['contract start date'] ?? '');

        return new NonInstallmentContractDto(
            header:                    $header,
            subject:                   $subject,
            providerContractNo:        $this->cleanSpaces($row['provider contract no'] ?? ''),
            contractEndActualDate:     $this->cleanSpaces($contract_end_actual_date),
            contractEndPlannedDate:    $this->cleanSpaces($contract_end_planned_date),
            contractPhase:             $this->cleanSpaces($row['contract phase'] ?? ''),
            contractStartDate:         $this->cleanSpaces($contract_start_date),
            contractType:              $this->cleanSpaces($row['contract type'] ?? ''),
            creditLimit:               $this->cleanSpaces($row['credit limit'] ?? ''),
            currency:                  $this->cleanSpaces($row['currency'] ?? ''),
            originalCurrency:          $this->cleanSpaces($row['original currency'] ?? ''),
            outstandingBalance:        $this->cleanSpaces($row['outstanding balance'] ?? ''),
            overduePaymentsAmount:     $this->cleanSpaces($row['overdue payments amount'] ?? ''),
            role:                      $this->cleanSpaces($row['role'] ?? ''),
            transactionType:           $this->cleanSpaces($row['transaction type sub facility'] ?? ''),
        );
    }

    private static function addLeadingZeroToDate(string $date): string {
        return strlen($date) === 7 ? '0' . $date : $date;
    }

    private static function cleanSpaces(string $text) {
        return trim(preg_replace('/\s+/', ' ', $text));
    }

    private static function mapToFamilyDto(array $row): FamilyDto {
        return new FamilyDto(
            spouseFirstName:      $this->cleanSpaces($row['spouse first name'] ?? ''),
            spouseLastName:       $this->cleanSpaces($row['spouse last name'] ?? ''),
            spouseMiddleName:     $this->cleanSpaces($row['spouse middle name'] ?? ''),
            motherMaidenFullName: $this->cleanSpaces($row['mother maiden full name'] ?? ''),
            fatherFirstName:      $this->cleanSpaces($row['father first name'] ?? ''),
            fatherLastName:       $this->cleanSpaces($row['father last name'] ?? ''),
            fatherMiddleName:     $this->cleanSpaces($row['father middle name'] ?? ''),
            fatherSuffix:         $this->cleanSpaces($row['father suffix'] ?? ''),
        );      
    }

    private static function mapToAddressDto(array $row): AddressDto {
        return new AddressDto(
            address1Type:        $this->cleanSpaces($row['address 1 address type'] ?? ''),
            address1FullAddress: $this->cleanSpaces($row['address 1 fulladdress'] ?? ''),
            address2Type:        $this->cleanSpaces($row['address 2 address type'] ?? ''),
            address2FullAddress: $this->cleanSpaces($row['address 2 fulladdress'] ?? ''),
        );
    }

    private static function mapToIdentificationDto(array $row): IdentificationDto {
        $identification1_number = !empty($row['identification 1 number']) ? (int) $row['identification 1 number'] : '';
        $identification2_number = !empty($row['identification 2 number']) ? (int) $row['identification 2 number'] : '';
        $id1_number = !empty($row['id 1 number']) ? (int) $row['id 1 number'] : '';
        $id2_number = !empty($row['id 2 number']) ? (int) $row['id 2 number'] : '';
        
        $id1_issuedate  = self::addLeadingZeroToDate($row['id 1 issuedate'] ?? '');
        $id1_expirydate  = self::addLeadingZeroToDate($row['id 1 expirydate'] ?? '');
        $id2_issuedate  = self::addLeadingZeroToDate($row['id 2 issuedate'] ?? '');
        $id2_expirydate  = self::addLeadingZeroToDate($row['id 2 expirydate'] ?? '');
        
        return new IdentificationDto(
            identification1Type:         $this->cleanSpaces($row['identification 1 type'] ?? ''),
            identification1Number:       $this->cleanSpaces($identification1_number),
            identification2Type:         $this->cleanSpaces($row['identification 2 type'] ?? ''),
            identification2Number:       $this->cleanSpaces($identification2_number),
            id1Type:                     $this->cleanSpaces($row['id 1 type'] ?? ''),
            id1Number:                   $this->cleanSpaces($id1_number),
            id1IssueDate:                $this->cleanSpaces($id1_issuedate),
            id1IssueCountry:             $this->cleanSpaces($row['id 1 issuecountry'] ?? ''),
            id1ExpiryDate:               $this->cleanSpaces($id1_expirydate),
            id1IssuedBy:                 $this->cleanSpaces($row['id 1 issued by'] ?? ''),
            id2Type:                     $this->cleanSpaces($row['id 2 type'] ?? ''),
            id2Number:                   $this->cleanSpaces($id2_number),
            id2IssueDate:                $this->cleanSpaces($id2_issuedate),
            id2IssueCountry:             $this->cleanSpaces($row['id 2 issuecountry'] ?? ''),
            id2ExpiryDate:               $this->cleanSpaces($id2_expirydate),
            id2IssuedBy:                 $this->cleanSpaces($row['id 2 issued by'] ?? ''),
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
            contact1Type:  $this->cleanSpaces($contact_1_type),
            contact1Value: $this->cleanSpaces($contact1_value),
            contact2Type:  $this->cleanSpaces($contact_2_type),
            contact2Value: $this->cleanSpaces($contact2_value),
        );
    }

    private static function mapToEmploymentDto(array $row): EmploymentDto {
        return new EmploymentDto(
            tradeName:        $this->cleanSpaces($row['employment trade name'] ?? ''),
            psic:             $this->cleanSpaces($row['employment psic'] ?? ''),
            occupationStatus: $this->cleanSpaces($row['employment occupationstatus'] ?? ''),
            occupation:       $this->cleanSpaces($row['employment occupation'] ?? ''),
        );
    }
}
?>