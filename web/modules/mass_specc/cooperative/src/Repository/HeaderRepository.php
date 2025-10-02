<?php

namespace Drupal\cooperative\Repository;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\HeaderDto;

class HeaderRepository {

    public function save(HeaderDto $dto): Node {
        $values = [
            'type' => 'header',
            'title' => "[$dto->providerCode - $dto->referenceDate] Header",
            'status' => 1,
            'field_provider_code' => $dto->providerCode,
            'field_branch_code' => $dto->branchCode,
            'field_reference_date' => $dto->referenceDate,
            'field_version' => $dto->version,
            'field_submission_type' => $dto->submissionType,
        ];
        $node = \Drupal::entityTypeManager()->getStorage('node')->create($values);
        $node->save();
        return $node;
    }

    public function findByCodesAndDate(string $providerCode, string $referenceDate, ?string $branchCode = ''): ?Node {
        $query = \Drupal::entityQuery('node')
        ->condition('type', 'header')
        ->condition('field_provider_code', $providerCode);

        if (!empty($branchCode)) {
            $query->condition('field_branch_code', $branchCode);
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