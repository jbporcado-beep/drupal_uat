<?php

namespace Drupal\cooperative\Validation;

use Drupal\node\Entity\Node;
use Drupal\cooperative\Dto\CompanyDto;
use Drupal\cooperative\Repository\CompanyRepository;

class CompanyValidator {
    private CompanyRepository $companyRepository;

    public function __construct(CompanyRepository $companyRepository) {
        $this->companyRepository = $companyRepository;
    }

    public function validate(CompanyDto $companyDto, array &$errors, int $row_number): void {
        $provider_code    = $companyDto->providerCode;
        $provider_subj_no = $companyDto->providerSubjectNo;
        $branch_code      = $companyDto->branchCode;
        $trade_name       = $companyDto->tradeName;

        $found_company = $this->companyRepository->findByMandatoryFields($companyDto);
        $is_provider_subj_no_taken = $this->companyRepository
            ->isProviderSubjNoTakenInCoopOrBranch($provider_code, $provider_subj_no, $branch_code);

        if ($is_provider_subj_no_taken && $found_company === null) {
            $errors[] = "Row $row_number | 10-090: THE SAME 'PROVIDER SUBJECT NO' IS ALREADY ASSIGNED TO ANOTHER SUBJECT";
        }

        if (empty($provider_subj_no)) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER SUBJECT NO' IS MANDATORY";
        }
        if (strlen($provider_subj_no) > 38) {
            $errors[] = "Row $row_number | FIELD 'PROVIDER SUBJECT NO' LENGTH MUST HAVE A LENGTH <= 38";
        }

        if (empty($trade_name)) {
            $errors[] = "Row $row_number | 10-032: FIELD 'TRADE NAME' IS MANDATORY";
        }
        if (strlen($trade_name) > 120) {
            $errors[] = "Row $row_number | FIELD 'TRADE NAME' LENGTH MUST HAVE A LENGTH <= 120"; 
        }

    }
}
?>