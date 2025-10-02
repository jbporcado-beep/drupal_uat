<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\CompanyDto;
use Drupal\cooperative\Dto\AddressDto;
use Drupal\cooperative\Dto\IdentificationDto;
use Drupal\cooperative\Dto\ContactDto;

class CompanyRepository {

    public function save(CompanyDto $companyDto): Node {
        $address_node = $this->saveAddress($companyDto->address);
        $identification_node = $this->saveIdentification($companyDto->identification);
        $contact_node = $this->saveContact($companyDto->contact);

        $values = [
            'type' => 'company',
            'title' => "[$companyDto->providerCode - $companyDto->providerSubjectNo] Company",
            'status' => 1,
            'field_provider_subject_no' => $companyDto->providerSubjectNo,
            'field_provider_code' => $companyDto->providerCode,
            'field_branch_code' => $companyDto->branchCode,
            'field_trade_name' => $companyDto->tradeName,
            'field_address' => $address_node,
            'field_identification' => $identification_node,
            'field_contact' => $contact_node,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }

    public function findByMandatoryFields(CompanyDto $companyDto): ?Node {
        $provider_subj_no        = $companyDto->providerSubjectNo;
        $provider_code           = $companyDto->providerCode;
        $branch_code             = $companyDto->branchCode;
        $trade_name              = $companyDto->tradeName;
        $identification_1_type   = $companyDto->identification->identification1Type;
        $identification_1_number = $companyDto->identification->identification1Number;
        $contact_1_type          = $companyDto->contact->contact1Type;
        $contact_1_value         = $companyDto->contact->contact1Value;

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'company')
            ->condition('field_provider_subject_no', $provider_subj_no)
            ->condition('field_provider_code', $provider_code);
    
        if (!empty($branch_code)) {
            $query->condition('field_branch_code', $branch_code);
        }

        $query->condition('field_trade_name', $trade_name)
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
        $provider_subj_no = $providerSubjNo;
        $provider_code    = $providerCode;
        $branch_code      = $branchCode;

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'company')
            ->condition('field_provider_subject_no', $provider_subj_no)
            ->condition('field_provider_code', $provider_code)
            ->accessCheck(TRUE);

        if (!empty($branch_code)) {
            $query->condition('field_branch_code', $branch_code);
        }

        $result = $query->count()->execute();

        return $result > 0;
    }

    public function findByCodes(
        string $providerCode, string $providerSubjNo, ?string $branchCode = ''
    ): ?Node {

        $query = \Drupal::entityQuery('node')
            ->condition('type', 'company')
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

}

?>