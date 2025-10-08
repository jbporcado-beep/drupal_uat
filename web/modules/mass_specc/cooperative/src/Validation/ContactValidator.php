<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\DomainLists;
use Drupal\cooperative\Dto\ContactDto;


class ContactValidator {
    const CONTACT_TYPE_DOMAIN = DomainLists::CONTACT_TYPE_DOMAIN;

    public function validate(ContactDto $contactDto, string $provider_subj_no, array &$errors, int $row_number): void {
        $contact_1_type    = $contactDto->contact1Type;
        $contact_1_value   = $contactDto->contact1Value;
        $contact_2_type    = $contactDto->contact2Type;
        $contact_2_value   = $contactDto->contact2Value;

        if (in_array($contact_1_type, ['1', '2']) && strlen($contact_1_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 1 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 1 TYPE' IS A LANDLINE PHONE";
        }

        if (in_array($contact_2_type, ['1', '2']) && strlen($contact_2_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 2 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 2 TYPE' IS A LANDLINE PHONE";
        }

        if (in_array($contact_1_type, ['3', '4']) && strlen($contact_1_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 1 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 1 TYPE' IS A MOBILE PHONE";
        }

        if (in_array($contact_2_type, ['3', '4']) && strlen($contact_2_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 2 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 2 TYPE' IS A MOBILE PHONE";
        }

        if (in_array($contact_1_type, ['5', '6']) && strlen($contact_1_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 1 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 1 TYPE' IS A FAX NUMBER";
        }

        if (in_array($contact_2_type, ['5', '6']) && strlen($contact_2_value) > 15) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 2 VALUE' LENGTH MUST HAVE A LENGTH <= 15 WHEN 'CONTACT 2 TYPE' IS A FAX NUMBER";
        }

        if (!empty($contact_1_type) && !in_array($contact_1_type, array_keys(self::CONTACT_TYPE_DOMAIN))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-014: FIELD 'CONTACT 1 TYPE' IS NOT CORRECT";
        }
        if (!empty($contact_2_type) && !in_array($contact_2_type, array_keys(self::CONTACT_TYPE_DOMAIN))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-014: FIELD 'CONTACT 2 TYPE' IS NOT CORRECT";
        }

        if (strlen($contact_1_value) > 100) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 1 VALUE' LENGTH MUST HAVE A LENGTH <= 100";
        }
        if (strlen($contact_2_value) > 100) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'CONTACT 2 VALUE' LENGTH MUST HAVE A LENGTH <= 100";
        }

        if ((!empty($contact_1_type) && empty($contact_1_value)) ||
            (empty($contact_1_type) && !empty($contact_1_value))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-074: FIELDS 'CONTACT 1 TYPE' AND 'CONTACT 1 VALUE' MUST " . 
                        "EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if ((!empty($contact_2_type) && empty($contact_2_value)) ||
            (empty($contact_2_type) && !empty($contact_2_value))) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-074: FIELDS 'CONTACT 2 TYPE' AND 'CONTACT 2 VALUE' MUST " . 
                        "EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if (!empty($contact_1_type) && ($contact_1_type === $contact_2_type)) {
            $errors[] = "$provider_subj_no | Row $row_number | 10-071: MORE THAN ONE 'CONTACT TYPE' WITH THE SAME VALUE ARE NOT ALLOWED";
        }

        if (empty($contact_1_type) && empty($contact_2_type)) {
            $errors[] = "$provider_subj_no | Row $row_number | 20-104: AT LEAST ONE BETWEEN ALL FIELDS 'CONTACT TYPE' SHOULD BE FILLED IN";
        }

    }
}
?>