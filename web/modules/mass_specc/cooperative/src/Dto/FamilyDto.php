<?php

namespace Drupal\cooperative\Dto;

class FamilyDto {
    public function __construct(
        public readonly ?string $spouseFirstName,
        public readonly ?string $spouseLastName,
        public readonly ?string $spouseMiddleName,
        public readonly ?string $motherMaidenFullName,
        public readonly ?string $fatherFirstName,
        public readonly ?string $fatherLastName,
        public readonly ?string $fatherMiddleName,
        public readonly ?string $fatherSuffix
    ) {}
}
?>