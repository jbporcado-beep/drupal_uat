<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\AddressDto;

class AddressValidator {
    
    public function validate(AddressDto $addressDto, string $provider_subj_no, array &$errors, int $row_number, string $record_type): void {
        $address_1_address_type  = $addressDto->address1Type;
        $address_1_fulladdress   = $addressDto->address1FullAddress;
        $address_2_address_type  = $addressDto->address2Type;
        $address_2_fulladdress   = $addressDto->address2FullAddress;


        if ($record_type === 'ID') {
            if ($address_1_address_type !== 'MI' && $address_2_address_type !== 'MI') {
                $errors[] = "$provider_subj_no | Row $row_number | 20-142: AT LEAST ONE OCCURENCE BETWEEN ALL INDIVIDUAL ADDRESSES " . 
                            "MUST BE 'MAIN ADDRESS'";
            }
            if ($address_1_address_type !== 'MI' || $address_2_address_type !== 'AI') {
                $errors[] = "$provider_subj_no | Row $row_number | 10-007: ADDRESS 1 TYPE MUST BE 'MI' AND ADDRESS 2 TYPE MUST BE 'AI'";
            }
            if (empty($address_1_address_type) || empty($address_1_fulladdress) || 
                empty($address_2_address_type) || empty($address_2_fulladdress)) {
                $errors[] = "$provider_subj_no | Row $row_number | 20-130: ALL 'ADDRESS:TYPE' MUST BE FILLED IN. " .
                            "TWO ADDRESSES MUST BE PROVIDED";
            }
        }
        else if ($record_type === 'BD') {
            if ($address_1_address_type !== 'MT' || (!empty($address_2_address_type) && $address_2_address_type !== 'AT')) {
                $errors[] = "$provider_subj_no | Row $row_number | 10-007: ADDRESS 1 TYPE MUST BE 'MT' AND ADDRESS 2 TYPE MUST BE 'AT'";
            }
        }

        if ((empty($address_1_address_type) && !empty($address_1_fulladdress)) ||
            (!empty($address_1_address_type) && empty($address_1_fulladdress))) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELDS 'ADDRESS 1 TYPE' AND 'ADDRESS 1 FULLADDRESS' " .
                        "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if ((empty($address_2_address_type) && !empty($address_2_fulladdress)) ||
            (!empty($address_2_address_type) && empty($address_2_fulladdress))) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELDS 'ADDRESS 1 TYPE' AND 'ADDRESS 1 FULLADDRESS' " .
                        "MUST EITHER BOTH BE EMPTY OR FILLED IN";
        }

        if (strlen($address_1_fulladdress) > 400 || strlen($address_2_fulladdress) > 400) {
            $errors[] = "$provider_subj_no | Row $row_number | FIELD 'ADDRESS: FULL ADDRESS' LENGTH MUST HAVE A LENGTH <= 400";  
        }
    }
}
?>