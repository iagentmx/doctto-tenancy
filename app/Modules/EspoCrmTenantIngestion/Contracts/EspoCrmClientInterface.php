<?php

namespace App\Modules\EspoCrmTenantIngestion\Contracts;

interface EspoCrmClientInterface
{
    public function getAccountById(string $id): array;

    public function getOpportunityById(string $id): array;

    public function getServiceById(string $id): array;

    public function getStaffById(string $id): array;
}
