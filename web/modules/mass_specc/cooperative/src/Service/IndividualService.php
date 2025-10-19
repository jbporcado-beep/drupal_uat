<?php

namespace Drupal\cooperative\Service;

use Drupal\cooperative\Utility\CsvToDtoMapper;
use Drupal\cooperative\Validation\IndividualValidator;
use Drupal\cooperative\Validation\FamilyValidator;
use Drupal\cooperative\Validation\AddressValidator;
use Drupal\cooperative\Validation\IdentificationValidator;
use Drupal\cooperative\Validation\ContactValidator;
use Drupal\cooperative\Validation\EmploymentValidator;
use Drupal\cooperative\Repository\IndividualRepository;

class IndividualService
{
    private IndividualRepository $individualRepository;
    private IndividualValidator $individualValidator;
    private FamilyValidator $familyValidator;
    private AddressValidator $addressValidator;
    private IdentificationValidator $identificationValidator;
    private ContactValidator $contactValidator;
    private EmploymentValidator $employmentValidator;
    private MemberProfileService $memberProfileService;

    public function __construct(
        IndividualRepository $individualRepository,
        IndividualValidator $individualValidator,
        FamilyValidator $familyValidator,
        AddressValidator $addressValidator,
        IdentificationValidator $identificationValidator,
        ContactValidator $contactValidator,
        EmploymentValidator $employmentValidator,
        MemberProfileService $memberProfileService,
    ) {
        $this->individualRepository = $individualRepository;
        $this->individualValidator = $individualValidator;
        $this->familyValidator = $familyValidator;
        $this->addressValidator = $addressValidator;
        $this->identificationValidator = $identificationValidator;
        $this->contactValidator = $contactValidator;
        $this->employmentValidator = $employmentValidator;
        $this->memberProfileService = $memberProfileService;
    }

    public function import(array $row, int $row_number, array &$errors)
    {

        $individualDto = CsvToDtoMapper::mapToIndividualDto($row);
        $provider_code = $individualDto->providerCode;
        $provider_subj_no = $individualDto->providerSubjectNo;
        $branch_code = $individualDto->branchCode;

        $familyDto = $individualDto->family;
        $addressDto = $individualDto->address;
        $identificationDto = $individualDto->identification;
        $contactDto = $individualDto->contact;
        $employmentDto = $individualDto->employment;

        $this->individualValidator->validate($individualDto, $errors, $row_number);
        $this->familyValidator->validate($familyDto, $provider_subj_no, $errors, $row_number);
        $this->addressValidator->validate($addressDto, $provider_subj_no, $errors, $row_number, "ID");
        $this->identificationValidator->validate($identificationDto, $provider_subj_no, $errors, $row_number, "ID");
        $this->contactValidator->validate($contactDto, $provider_subj_no, $errors, $row_number);
        $this->employmentValidator->validate($employmentDto, $provider_subj_no, $errors, $row_number);

        $is_provider_subj_no_taken = $this->individualRepository
            ->isProviderSubjNoTakenInCoopOrBranch($provider_code, $provider_subj_no, $branch_code);

        if (!$is_provider_subj_no_taken && empty($errors)) {
            $this->individualRepository->save($individualDto);
            $individual_node = $this->individualRepository->save($individualDto);
            $identity = [
                'first_name' => $individualDto->firstName,
                'last_name' => $individualDto->lastName,
                'dob' => $individualDto->dateOfBirth,
                'gender' => $individualDto->gender,
            ];

            $member_profile = $this->memberProfileService->findOrCreateMemberProfile($identity);

            if ($member_profile) {
                $individual_node->set('field_member_profile', $member_profile->id());
                $individual_node->save();

                if ($member_profile->hasField('field_individual_profiles')) {
                    $existing_refs = array_column($member_profile->get('field_individual_profiles')->getValue(), 'target_id');
                    if (!in_array($individual_node->id(), $existing_refs)) {
                        $member_profile->get('field_individual_profiles')->appendItem(['target_id' => $individual_node->id()]);
                        $member_profile->save();
                    }
                }
            }
        }
    }
}
?>