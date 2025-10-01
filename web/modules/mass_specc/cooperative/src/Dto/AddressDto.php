<?php

namespace Drupal\cooperative\Dto;

class AddressDto {
    public function __construct(
        public readonly string $address1Type,
        public readonly string $address1FullAddress,
        public readonly ?string $address2Type,
        public readonly ?string $address2FullAddress,
    ) {}
}
?>