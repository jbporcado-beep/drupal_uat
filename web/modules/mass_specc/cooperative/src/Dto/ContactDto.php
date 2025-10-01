<?php

namespace Drupal\cooperative\Dto;

class ContactDto {
    public function __construct(
        public readonly ?string $contact1Type,
        public readonly ?string $contact1Value,
        public readonly ?string $contact2Type,
        public readonly ?string $contact2Value,
    ) {}
}
?>