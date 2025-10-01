<?php

namespace Drupal\cooperative\Dto;

class IndividualDto {
    public function __construct(
        public readonly string $providerSubjectNo,
        public readonly string $providerCode,
        public readonly string $branchCode,
        public readonly FamilyDto $family,
        public readonly AddressDto $address,
        public readonly IdentificationDto $identification,
        public readonly ContactDto $contact,
        public readonly EmploymentDto $employment,
        public readonly string $title,
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly ?string $middleName,
        public readonly ?string $suffix,
        public readonly ?string $previousLastName,
        public readonly string $gender,
        public readonly string $dateOfBirth,
        public readonly string $placeOfBirth,
        public readonly string $countryOfBirthCode,
        public readonly string $nationality,
        public readonly string $resident,
        public readonly string $civilStatus,
        public readonly string $numberOfDependents,
        public readonly string $carsOwned
    ) {}
}
?>