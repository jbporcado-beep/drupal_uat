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
            providerCode:   self::cleanSpaces($row['provider code'] ?? ''),
            branchCode:     self::cleanSpaces($row['branch code'] ?? ''),
            referenceDate:  self::cleanSpaces($reference_date),
            version:        self::cleanSpaces($row['version'] ?? '1.0'),
            submissionType: self::cleanSpaces($row['submission type'] ?? '1')
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

            providerSubjectNo:   self::cleanSpaces($row['provider subject no'] ?? ''),
            providerCode:        self::cleanSpaces($row['provider code'] ?? ''),
            branchCode:          self::cleanSpaces($row['branch code'] ?? ''),
            title:               self::cleanSpaces($row['title'] ?? ''),
            firstName:           self::cleanSpaces($row['first name'] ?? ''),
            lastName:            self::cleanSpaces($row['last name'] ?? ''),
            middleName:          self::cleanSpaces($row['middle name'] ?? ''),
            suffix:              self::cleanSpaces($row['suffix'] ?? ''),
            previousLastName:    self::cleanSpaces($row['previous last name'] ?? ''),
            gender:              self::cleanSpaces($row['gender'] ?? ''),
            dateOfBirth:         self::cleanSpaces($date_of_birth),
            placeOfBirth:        self::cleanSpaces($row['place of birth'] ?? ''),
            countryOfBirthCode:  self::cleanSpaces($row['country of birth code'] ?? ''),
            nationality:         self::cleanSpaces($row['nationality'] ?? ''),
            resident:            self::cleanSpaces($row['resident'] ?? ''),
            civilStatus:         self::cleanSpaces($row['civil status'] ?? ''),
            numberOfDependents:  self::cleanSpaces($row['number of dependents'] ?? ''),
            carsOwned:           self::cleanSpaces($row['cars owned'] ?? '')
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

            providerSubjectNo:   self::cleanSpaces($row['provider subject no'] ?? ''),
            providerCode:        self::cleanSpaces($row['provider code'] ?? ''),
            branchCode:          self::cleanSpaces($row['branch code'] ?? ''),
            tradeName:           self::cleanSpaces($row['trade name'] ?? ''),
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
            providerContractNo:        self::cleanSpaces($row['provider contract no'] ?? ''),
            contractEndActualDate:     self::cleanSpaces($contract_end_actual_date),
            contractEndPlannedDate:    self::cleanSpaces($contract_end_planned_date),
            contractPhase:             self::cleanSpaces($row['contract phase'] ?? ''),
            contractStartDate:         self::cleanSpaces($contract_start_date),
            contractType:              self::cleanSpaces($row['contract type'] ?? ''),
            currency:                  self::cleanSpaces($row['currency'] ?? ''),
            financedAmount:            self::cleanSpaces($row['financed amount'] ?? ''),
            installmentsNumber:        self::cleanSpaces($row['installments number'] ?? ''),
            lastPaymentAmount:         self::cleanSpaces($row['last payment amount'] ?? ''),
            monthlyPaymentAmount:      self::cleanSpaces($row['monthly payment amount'] ?? ''),
            nextPaymentDate:           self::cleanSpaces($next_payment_date),
            originalCurrency:          self::cleanSpaces($row['original currency'] ?? ''),
            outstandingBalance:        self::cleanSpaces($row['outstanding balance'] ?? ''),
            outstandingPaymentsNumber: self::cleanSpaces($row['outstanding payments number'] ?? ''),
            overdueDays:               self::cleanSpaces($row['overdue days'] ?? ''),
            overduePaymentsAmount:     self::cleanSpaces($row['overdue payments amount'] ?? ''),
            overduePaymentsNumber:     self::cleanSpaces($row['overdue payments number'] ?? ''),
            paymentPeriodicity:        self::cleanSpaces($row['payment periodicity'] ?? ''),
            role:                      self::cleanSpaces($row['role'] ?? ''),
            transactionType:           self::cleanSpaces($row['transaction type sub facility'] ?? ''),
        );
    }

    public static function mapToNonInstallmentContractDto(array $row, ?Node $header, ?Node $subject): NonInstallmentContractDto {
        $contract_end_actual_date  = self::addLeadingZeroToDate($row['contract end actual date'] ?? '');
        $contract_end_planned_date = self::addLeadingZeroToDate($row['contract end planned date'] ?? '');
        $contract_start_date       = self::addLeadingZeroToDate($row['contract start date'] ?? '');

        return new NonInstallmentContractDto(
            header:                    $header,
            subject:                   $subject,
            providerContractNo:        self::cleanSpaces($row['provider contract no'] ?? ''),
            contractEndActualDate:     self::cleanSpaces($contract_end_actual_date),
            contractEndPlannedDate:    self::cleanSpaces($contract_end_planned_date),
            contractPhase:             self::cleanSpaces($row['contract phase'] ?? ''),
            contractStartDate:         self::cleanSpaces($contract_start_date),
            contractType:              self::cleanSpaces($row['contract type'] ?? ''),
            creditLimit:               self::cleanSpaces($row['credit limit'] ?? ''),
            currency:                  self::cleanSpaces($row['currency'] ?? ''),
            originalCurrency:          self::cleanSpaces($row['original currency'] ?? ''),
            outstandingBalance:        self::cleanSpaces($row['outstanding balance'] ?? ''),
            overduePaymentsAmount:     self::cleanSpaces($row['overdue payments amount'] ?? ''),
            role:                      self::cleanSpaces($row['role'] ?? ''),
            transactionType:           self::cleanSpaces($row['transaction type sub facility'] ?? ''),
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
            spouseFirstName:      self::cleanSpaces($row['spouse first name'] ?? ''),
            spouseLastName:       self::cleanSpaces($row['spouse last name'] ?? ''),
            spouseMiddleName:     self::cleanSpaces($row['spouse middle name'] ?? ''),
            motherMaidenFullName: self::cleanSpaces($row['mother maiden full name'] ?? ''),
            fatherFirstName:      self::cleanSpaces($row['father first name'] ?? ''),
            fatherLastName:       self::cleanSpaces($row['father last name'] ?? ''),
            fatherMiddleName:     self::cleanSpaces($row['father middle name'] ?? ''),
            fatherSuffix:         self::cleanSpaces($row['father suffix'] ?? ''),
        );      
    }

    private static function mapToAddressDto(array $row): AddressDto {
        return new AddressDto(
            address1Type:        self::cleanSpaces($row['address 1 address type'] ?? ''),
            address1FullAddress: self::cleanSpaces($row['address 1 fulladdress'] ?? ''),
            address2Type:        self::cleanSpaces($row['address 2 address type'] ?? ''),
            address2FullAddress: self::cleanSpaces($row['address 2 fulladdress'] ?? ''),
        );
    }

    private static function mapToIdentificationDto(array $row): IdentificationDto {

        $id1_issuedate  = self::addLeadingZeroToDate($row['id 1 issuedate'] ?? '');
        $id1_expirydate  = self::addLeadingZeroToDate($row['id 1 expirydate'] ?? '');
        $id2_issuedate  = self::addLeadingZeroToDate($row['id 2 issuedate'] ?? '');
        $id2_expirydate  = self::addLeadingZeroToDate($row['id 2 expirydate'] ?? '');
        
        return new IdentificationDto(
            identification1Type:         self::cleanSpaces($row['identification 1 type'] ?? ''),
            identification1Number:       self::cleanSpaces($row['identification 1 number'] ?? ''),
            identification2Type:         self::cleanSpaces($row['identification 2 type'] ?? ''),
            identification2Number:       self::cleanSpaces($row['identification 2 number'] ?? ''),
            id1Type:                     self::cleanSpaces($row['id 1 type'] ?? ''),
            id1Number:                   self::cleanSpaces($row['id 1 number'] ?? ''),
            id1IssueDate:                self::cleanSpaces($id1_issuedate),
            id1IssueCountry:             self::cleanSpaces($row['id 1 issuecountry'] ?? ''),
            id1ExpiryDate:               self::cleanSpaces($id1_expirydate),
            id1IssuedBy:                 self::cleanSpaces($row['id 1 issued by'] ?? ''),
            id2Type:                     self::cleanSpaces($row['id 2 type'] ?? ''),
            id2Number:                   self::cleanSpaces($row['id 2 number'] ?? ''),
            id2IssueDate:                self::cleanSpaces($id2_issuedate),
            id2IssueCountry:             self::cleanSpaces($row['id 2 issuecountry'] ?? ''),
            id2ExpiryDate:               self::cleanSpaces($id2_expirydate),
            id2IssuedBy:                 self::cleanSpaces($row['id 2 issued by'] ?? ''),
        );
    }

    private static function mapToContactDto(array $row): ContactDto {
        $contact_1_type = $row['contact 1 type'] ?? '';
        $contact_2_type = $row['contact 2 type'] ?? '';
        $contact_1_value = $row['contact 1 value'] ?? '';
        $contact_2_value = $row['contact 2 value'] ?? '';

        return new ContactDto(
            contact1Type:  self::cleanSpaces($contact_1_type),
            contact1Value: self::cleanSpaces($contact_1_value),
            contact2Type:  self::cleanSpaces($contact_2_type),
            contact2Value: self::cleanSpaces($contact_2_value),
        );
    }

    private static function mapToEmploymentDto(array $row): EmploymentDto {
        return new EmploymentDto(
            tradeName:        self::cleanSpaces($row['employment trade name'] ?? ''),
            psic:             self::cleanSpaces($row['employment psic'] ?? ''),
            occupationStatus: self::cleanSpaces($row['employment occupationstatus'] ?? ''),
            occupation:       self::cleanSpaces($row['employment occupation'] ?? ''),
        );
    }
}
?>