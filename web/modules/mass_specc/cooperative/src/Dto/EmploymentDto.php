<?php

namespace Drupal\cooperative\Dto;

class EmploymentDto {
    public function __construct(
        public readonly ?string $tradeName,
        public readonly ?string $psic,
        public readonly ?string $occupationStatus,
        public readonly ?string $occupation
    ) {}
}
?>