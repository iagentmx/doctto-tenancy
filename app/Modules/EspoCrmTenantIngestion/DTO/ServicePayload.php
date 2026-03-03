<?php

namespace App\Modules\EspoCrmTenantIngestion\DTO;

final class ServicePayload
{
    public function __construct(public readonly array $payload) {}

    public static function fromArray(array $payload): self
    {
        return new self($payload);
    }

    public function toArray(): array
    {
        return $this->payload;
    }
}
