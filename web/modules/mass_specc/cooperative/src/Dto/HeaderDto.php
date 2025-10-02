<?php

namespace Drupal\cooperative\Dto;

class HeaderDto {
    public function __construct(
        public readonly string $providerCode,
        public readonly ?string $branchCode,
        public readonly string $referenceDate,
        public readonly string $version,
        public readonly string $submissionType,
    ) {}
}
?>