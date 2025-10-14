<?php

namespace Drupal\cooperative\Service;

use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Validation\HeaderValidator;
use Drupal\cooperative\Repository\HeaderRepository;

class HeaderService {
    private HeaderRepository $headerRepository;
    private HeaderValidator $headerValidator;

    public function __construct(
        HeaderRepository $headerRepository, 
        HeaderValidator $headerValidator,
    ) {
        $this->headerRepository = $headerRepository;
        $this->headerValidator = $headerValidator;
    }

    public function import(array $row, int $row_number, array &$errors) {
        $headerDto = CsvToDtoMapper::mapToHeaderDto($row);
        $provider_code = $headerDto->providerCode ?? '';
        $reference_date = $headerDto->referenceDate ?? '';
        $branch_code = $headerDto->branchCode ?? '';

        $this->headerValidator->validate($headerDto, $errors, $row_number);

        $header_node = $this->headerRepository
            ->findByCodesAndDate($provider_code, $reference_date, $branch_code);
    
        if ($header_node === null && empty($errors)) {
            $this->headerRepository->save($headerDto);
        }
    }
}
?>