<?php

namespace Drupal\cooperative\Service;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Dto\IndividualDto;
use Drupal\cooperative\Repository\HeaderRepository;
use Drupal\cooperative\Repository\IndividualRepository;
use Drupal\cooperative\Repository\CompanyRepository;
use Drupal\cooperative\Repository\NonInstallmentContractRepository;
use Drupal\cooperative\Validation\NonInstallmentContractValidator;

class NonInstallmentContractService {
    private HeaderRepository $headerRepository;
    private IndividualRepository $individualRepository;
    private CompanyRepository $companyRepository;
    private NonInstallmentContractRepository $nonInstallmentContractRepository;
    private NonInstallmentContractValidator $nonInstallmentContractValidator;

    public function __construct(
        HeaderRepository $headerRepository,
        IndividualRepository $individualRepository, 
        CompanyRepository $companyRepository,
        NonInstallmentContractRepository $nonInstallmentContractRepository,
        NonInstallmentContractValidator $nonInstallmentContractValidator
    ) {
        $this->headerRepository = $headerRepository;
        $this->individualRepository = $individualRepository;
        $this->companyRepository = $companyRepository;
        $this->nonInstallmentContractRepository = $nonInstallmentContractRepository;
        $this->nonInstallmentContractValidator = $nonInstallmentContractValidator;
    }

    public function import(array $row, int $row_number, array &$errors) {

        $provider_code        = $row['provider code'] ?? '';
        $provider_subj_no     = $row['provider subject no'] ?? '';
        $provider_contract_no = $row['provider contract no'] ?? '';
        $branch_code          = $row['branch code'] ?? '';
        $reference_date       = $row['reference date'] ?? '';

        $header  = $this->headerRepository->findByCodesAndDate($provider_code, $reference_date, $branch_code);
        $subject = $this->individualRepository->findByCodes($provider_code, $provider_subj_no, $branch_code);
        if ($subject === null) {
            $subject = $this->companyRepository->findByCodes($provider_code, $provider_subj_no, $branch_code);
        }

        $nonInstallmentContractDto = CsvToDtoMapper::mapToNonInstallmentContractDto($row, $header, $subject);

        $this->nonInstallmentContractValidator->validate($nonInstallmentContractDto, $errors, $row_number);

        $existingContract = $this->nonInstallmentContractRepository
            ->findByCodes($provider_code, $provider_contract_no, $branch_code);

        if (empty($errors)) {
            if ($existingContract === null) {
                $this->nonInstallmentContractRepository->save($nonInstallmentContractDto);
            }
            else {
                $this->nonInstallmentContractRepository->update($nonInstallmentContractDto, $existingContract);
            }
        }
    }
}
?>