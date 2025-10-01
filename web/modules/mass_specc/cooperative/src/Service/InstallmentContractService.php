<?php

namespace Drupal\cooperative\Service;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Dto\IndividualDto;
use Drupal\cooperative\Repository\HeaderRepository;
use Drupal\cooperative\Repository\IndividualRepository;
use Drupal\cooperative\Repository\CompanyRepository;
use Drupal\cooperative\Repository\InstallmentContractRepository;
use Drupal\cooperative\Validation\InstallmentContractValidator;

class InstallmentContractService {
    private HeaderRepository $headerRepository;
    private IndividualRepository $individualRepository;
    private CompanyRepository $companyRepository;
    private InstallmentContractRepository $installmentContractRepository;
    private InstallmentContractValidator $installmentContractValidator;

    public function __construct(
        HeaderRepository $headerRepository,
        IndividualRepository $individualRepository, 
        CompanyRepository $companyRepository,
        InstallmentContractRepository $installmentContractRepository,
        InstallmentContractValidator $installmentContractValidator
    ) {
        $this->headerRepository = $headerRepository;
        $this->individualRepository = $individualRepository;
        $this->companyRepository = $companyRepository;
        $this->installmentContractRepository = $installmentContractRepository;
        $this->installmentContractValidator = $installmentContractValidator;
    }

    public function import(array $row, int $row_number, array &$errors) {

        $provider_code        = $row['provider code'] ?? '';
        $provider_subj_no     = $row['provider subject no'] ?? '';
        $provider_contract_no = $row['provider contract no'] ?? '';
        $branch_code          = $row['branch code'] ?? '';
        $reference_date       = $row['reference date'] ?? '';

        $header  = $this->headerRepository->findByCodes($provider_code, $reference_date, $branch_code);
        $subject = $this->individualRepository->findByCodes($provider_code, $provider_subj_no, $branch_code);
        if ($subject === null) {
            $subject = $this->companyRepository->findByCodes($provider_code, $provider_subj_no, $branch_code);
        }

        $installmentContractDto = CsvToDtoMapper::mapToInstallmentContractDto($row, $header, $subject);

        $this->installmentContractValidator->validate($installmentContractDto, $errors, $row_number);

        $existingContract = $this->installmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);

        if (empty($errors)) {
            if ($existingContract === null) {
                $this->installmentContractRepository->save($installmentContractDto);
            }
            else {
                $this->installmentContractRepository->update($installmentContractDto, $existingContract);
            }
        }
    }
}
?>