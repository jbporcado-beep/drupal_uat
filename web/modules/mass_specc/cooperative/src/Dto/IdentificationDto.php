<?php

namespace Drupal\cooperative\Dto;

class IdentificationDto {
    public function __construct(
        public readonly string $identification1Type,
        public readonly string $identification1Number,
        public readonly ?string $identification2Type,
        public readonly ?string $identification2Number,
        public readonly ?string $id1Type,
        public readonly ?string $id1Number,
        public readonly ?string $id1IssueDate,
        public readonly ?string $id1IssueCountry,
        public readonly ?string $id1ExpiryDate,
        public readonly ?string $id1IssuedBy,
        public readonly ?string $id2Type,
        public readonly ?string $id2Number,
        public readonly ?string $id2IssueDate,
        public readonly ?string $id2IssueCountry,
        public readonly ?string $id2ExpiryDate,
        public readonly ?string $id2IssuedBy,
    ) {}
}
?>