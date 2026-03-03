<?php

namespace App\Repositories;

use App\Models\Service;
use App\Repositories\Contracts\ServiceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class ServiceRepository implements ServiceRepositoryInterface
{
    public function allServices(): Collection
    {
        return Service::all();
    }

    public function findServiceByTenant(int $tenantId): Collection
    {
        return Service::where('tenant_id', $tenantId)->get();
    }

    public function updateOrCreateService(array $where, array $data): Service
    {
        return Service::updateOrCreate($where, $data);
    }
}
