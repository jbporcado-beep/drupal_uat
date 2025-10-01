<?php

namespace Drupal\cooperative\Service;

use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Dto\CompanyDto;
use Drupal\cooperative\Validation\CompanyValidator;
use Drupal\cooperative\Validation\AddressValidator;
use Drupal\cooperative\Validation\IdentificationValidator;
use Drupal\cooperative\Validation\ContactValidator;
use Drupal\cooperative\Repository\CompanyRepository;

class CompanyService {
    private CompanyRepository $companyRepository;
    private CompanyValidator $companyValidator;
    private AddressValidator $addressValidator;
    private IdentificationValidator $identificationValidator;
    private ContactValidator $contactValidator;

    public function __construct(
        CompanyRepository $companyRepository, 
        CompanyValidator $companyValidator,
        AddressValidator $addressValidator,
        IdentificationValidator $identificationValidator,
        ContactValidator $contactValidator,
    ) {
        $this->companyRepository = $companyRepository;
        $this->companyValidator = $companyValidator;
        $this->addressValidator = $addressValidator;
        $this->identificationValidator = $identificationValidator;
        $this->contactValidator = $contactValidator;
    }

    public function import(array $row, int $row_number, array &$errors) {

        $companyDto = CsvToDtoMapper::mapToCompanyDto($row);
        $provider_code = $companyDto->providerCode;
        $provider_subj_no = $companyDto->providerSubjectNo;
        $branch_code = $companyDto->branchCode;

        $addressDto        = $companyDto->address;
        $identificationDto = $companyDto->identification;
        $contactDto        = $companyDto->contact;

        $this->companyValidator->validate($companyDto, $errors, $row_number);
        $this->addressValidator->validate($addressDto, $errors, $row_number, "BD");
        $this->identificationValidator->validate($identificationDto, $errors, $row_number, "BD");
        $this->contactValidator->validate($contactDto, $errors, $row_number);

        $is_provider_subj_no_taken = $this->companyRepository
            ->isProviderSubjNoTakenInCoopOrBranch($provider_code, $provider_subj_no, $branch_code);
        
        if (!$is_provider_subj_no_taken && empty($errors)) {
            $this->companyRepository->save($companyDto);
        }
    }
}
?>