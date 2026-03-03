<?php

namespace App\Repositories\Contracts;

use Illuminate\Database\Eloquent\Collection;
use App\Models\Service;

interface ServiceRepositoryInterface
{
    public function allServices(): Collection;
    public function findServiceByTenant(int $tenantId): Collection;
    public function updateOrCreateService(array $where, array $data): Service;
}
