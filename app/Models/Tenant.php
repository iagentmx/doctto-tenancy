<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Enums\IndustryType;
use App\Enums\OperationType;

class Tenant extends Model
{
    use HasFactory;

    protected $fillable = [
        'jid',
        'name',
        'is_active',
        'espocrm_id',
        'industry_type',
        'operation_type',
        'description',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'settings' => 'array',
        'industry_type' => IndustryType::class,
        'operation_type' => OperationType::class,
    ];

    public function staff()
    {
        return $this->hasMany(Staff::class);
    }

    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function serviceCategories()
    {
        return $this->hasMany(ServiceCategory::class);
    }

    public function tenantLocations()
    {
        return $this->hasMany(TenantLocation::class);
    }

    public function resources()
    {
        return $this->hasMany(Resource::class);
    }

    public function primaryLocation()
    {
        return $this->hasOne(TenantLocation::class)->where('is_primary', true);
    }

    public function tenantAdmins()
    {
        return $this->hasMany(TenantAdmin::class);
    }
}
