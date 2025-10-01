<?php

namespace Drupal\cooperative\Dto;

class CompanyDto {
    public function __construct(
        public readonly string $providerSubjectNo,
        public readonly string $providerCode,
        public readonly string $branchCode,
        public readonly string $tradeName,
        public readonly AddressDto $address,
        public readonly IdentificationDto $identification,
        public readonly ContactDto $contact
    ) {}
}
?>