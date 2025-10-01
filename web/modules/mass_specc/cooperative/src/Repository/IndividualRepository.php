<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\IndividualDto;
use Drupal\cooperative\Dto\FamilyDto;
use Drupal\cooperative\Dto\AddressDto;
use Drupal\cooperative\Dto\IdentificationDto;
use Drupal\cooperative\Dto\ContactDto;
use Drupal\cooperative\Dto\EmploymentDto;

class IndividualRepository {

    public function save(IndividualDto $individualDto): Node {
        $family_node = $this->saveFamily($individualDto->family);
        $address_node = $this->saveAddress($individualDto->address);
        $identification_node = $this->saveIdentification($individualDto->identification);
        $contact_node = $this->saveContact($individualDto->contact);
        $employment_node = $this->saveEmployment($individualDto->employment);

        $values = [
            'type' => 'individual',
            'title' => "[$individualDto->providerCode - $individualDto->providerSubjectNo] Individual",
            'status' => 1,
            'field_provider_subject_no' => $individualDto->providerSubjectNo,
            'field_provider_code' => $individualDto->providerCode,
            'field_branch_code' => $individualDto->branchCode,
            'field_family' => $family_node,
            'field_address' => $address_node,
            'field_identification' => $identification_node,
            'field_contact' => $contact_node,
            'field_employment' => $employment_node,
            'field_title' => $individualDto->title,
            'field_first_name' => $individualDto->firstName,
            'field_last_name' => $individualDto->lastName,
            'field_middle_name' => $individualDto->middleName,
            'field_suffix' => $individualDto->suffix,
            'field_previous_last_name' => $individualDto->previousLastName,
            'field_gender' => $individualDto->gender,
            'field_date_of_birth' => $individualDto->dateOfBirth,
            'field_place_of_birth' => $individualDto->placeOfBirth,
            'field_country_of_birth_code' => $individualDto->countryOfBirthCode,
            'field_nationality' => $individualDto->nationality,
            'field_resident' => $individualDto->resident,
            'field_civil_status' => $individualDto->civilStatus,
            'field_number_of_dependents' => $individualDto->numberOfDependents,
            'field_cars_owned' => $individualDto->carsOwned,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }

    public function findByMandatoryFields(IndividualDto $individualDto): ?Node {
        $provider_subj_no        = $individualDto->providerSubjectNo;
        $provider_code           = $individualDto->providerCode;
        $branchCode              = $individualDto->branchCode;
        $first_name              = $individualDto->firstName;
        $last_name               = $individualDto->lastName;
        $gender                  = $individualDto->gender;
        $date_of_birth           = $individualDto->dateOfBirth;
        $identification_1_type   = $individualDto->identification->identification1Type;
        $identification_1_number = $individualDto->identification->identification1Number;
        $contact_1_type          = $individualDto->contact->contact1Type;
        $contact_1_value         = $individualDto->contact->contact1Value;

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'individual')
            ->condition('field_provider_subject_no', $provider_subj_no)
            ->condition('field_provider_code', $provider_code);
            
        if (!empty($branch_code)) {
            $query->condition('field_branch_code', $branch_code);
        }

        $query->condition('field_first_name', $first_name)
            ->condition('field_last_name', $last_name)
            ->condition('field_gender', $gender)
            ->condition('field_date_of_birth', $date_of_birth)
            ->condition('field_identification.entity.field_identification1_type', $identification_1_type)
            ->condition('field_identification.entity.field_identification1_number', $identification_1_number)
            ->condition('field_contact.entity.field_contact1_type', $contact_1_type)
            ->condition('field_contact.entity.field_contact1_value', $contact_1_value)
            ->accessCheck(TRUE)
            ->range(0, 1);

        $result = $query->execute();
        if (!empty($result)) {
            $nid = reset($result);
            $node = \Drupal::entityTypeManager()->getStorage('node')->load($nid);
            return $node;
        }
        return null;
    }

    public function isProviderSubjNoTakenInCoopOrBranch(
        string $providerCode, string $providerSubjNo, string $branchCode = ''
    ): bool {
        $query = \Drupal::entityQuery('node')
            ->condition('type', 'individual')
            ->condition('field_provider_code', $providerCode)
            ->condition('field_provider_subject_no', $providerSubjNo)
            ->accessCheck(TRUE);

        if (!empty($branchCode)) {
            $query->condition('field_branch_code', $branchCode);
        }

        $result = $query->count()->execute();

        return $result > 0;
    }

    public function findByCodes(
        string $providerCode, string $providerSubjNo, string $branchCode = ''
    ): ?Node {

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'individual')
            ->condition('field_provider_code', $providerCode)
            ->condition('field_provider_subject_no', $providerSubjNo)
            ->accessCheck(TRUE);

        if (!empty($branchCode)) {
            $query->condition('field_branch_code', $branchCode);
        }

        $query->range(0, 1);

        $result = $query->execute();
        if (!empty($result)) {
            $nid = reset($result);
            $node = Node::load($nid);
            return $node;
        }
        return null;
    }

    private function saveFamily(FamilyDto $familyDto): Node {
        $values = [
            'type' => 'family',
            'title' => "Family",
            'status' => 1,
            'field_spouse_first_name' => $familyDto->spouseFirstName,
            'field_spouse_last_name' => $familyDto->spouseLastName,
            'field_spouse_middle_name' => $familyDto->spouseMiddleName,
            'field_mother_maiden_full_name' => $familyDto->motherMaidenFullName,
            'field_father_first_name' => $familyDto->fatherFirstName,
            'field_father_last_name' => $familyDto->fatherLastName,
            'field_father_middle_name' => $familyDto->fatherMiddleName,
            'field_father_suffix' => $familyDto->fatherSuffix
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }
    private function saveAddress(AddressDto $addressDto): Node {
        $values = [
            'type' => 'address',
            'title' => "Address",
            'status' => 1,
            'field_address1_type' => $addressDto->address1Type,
            'field_address1_fulladdress' => $addressDto->address1FullAddress,
            'field_address2_type' => $addressDto->address2Type,
            'field_address2_fulladdress' => $addressDto->address2FullAddress
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }
    private function saveIdentification(IdentificationDto $identificationDto): Node {
        $values = [
            'type' => 'identification',
            'title' => "Identification",
            'status' => 1,
            'field_identification1_type' => $identificationDto->identification1Type,
            'field_identification1_number' => $identificationDto->identification1Number,
            'field_identification2_type' => $identificationDto->identification2Type,
            'field_identification2_number' => $identificationDto->identification2Number,
            'field_id1_type' => $identificationDto->id1Type,
            'field_id1_number' => $identificationDto->id1Number,
            'field_id1_issuedate' => $identificationDto->id1IssueDate,
            'field_id1_issuecountry' => $identificationDto->id1IssueCountry,
            'field_id1_expirydate' => $identificationDto->id1ExpiryDate,
            'field_id1_issuedby' => $identificationDto->id1IssuedBy,
            'field_id2_type' => $identificationDto->id2Type,
            'field_id2_number' => $identificationDto->id2Number,
            'field_id2_issuedate' => $identificationDto->id2IssueDate,
            'field_id2_issuecountry' => $identificationDto->id2IssueCountry,
            'field_id2_expirydate' => $identificationDto->id2ExpiryDate,
            'field_id2_issuedby' => $identificationDto->id2IssuedBy,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }
    private function saveContact(ContactDto $contactDto): Node {
        $values = [
            'type' => 'contact',
            'title' => "Contact",
            'status' => 1,
            'field_contact1_type' => $contactDto->contact1Type,
            'field_contact1_value' => $contactDto->contact1Value,
            'field_contact2_type' => $contactDto->contact2Type,
            'field_contact2_value' => $contactDto->contact2Value,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }
    private function saveEmployment(EmploymentDto $employmentDto): Node {
        $values = [
            'type' => 'employment',
            'title' => "Employment",
            'status' => 1,
            'field_employ_trade_name' => $employmentDto->tradeName,
            'field_employ_psic' => $employmentDto->psic,
            'field_employ_occupation_status' => $employmentDto->occupationStatus,
            'field_employ_occupation' => $employmentDto->occupation,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }

}

?>