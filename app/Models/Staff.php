<?php

namespace App\Models;

use App\Enums\StaffRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Staff extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'espocrm_id',
        'name',
        'role',
        'phone',
        'email',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'role'      => StaffRole::class,
        'is_active' => 'boolean',
        'settings'  => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'staff_services')
            ->using(StaffService::class);
    }

    public function schedules()
    {
        return $this->hasMany(StaffSchedule::class);
    }
}
