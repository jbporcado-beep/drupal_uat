<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\CompanyDto;
use Drupal\cooperative\Dto\FamilyDto;
use Drupal\cooperative\Dto\AddressDto;
use Drupal\cooperative\Dto\IdentificationDto;
use Drupal\cooperative\Dto\ContactDto;
use Drupal\cooperative\Dto\EmploymentDto;

class HeaderRepository {
    public function findByCodes(string $providerCode, string $referenceDate, string $branchCode = ''): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'header')
        ->condition('field_provider_code', $providerCode);

        if (!empty($branchCode)) {
            $query->condition('field_header_branch_code', $branchCode);
        }

        $query->condition('field_reference_date', $referenceDate)
            ->accessCheck(TRUE)
            ->range(0, 1);

        $result = $query->execute();
        if (!empty($result)) {
            $nid = reset($result);
            $node = Node::load($nid);
            return $node;
        }
        return null;
    }

}

?>