<?php

namespace App\Models;

use App\Enums\SchedulableType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Schedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'schedulable_type',
        'schedulable_id',
        'tenant_location_id',
        'day_of_week',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'schedulable_type' => SchedulableType::class,
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function tenantLocation()
    {
        return $this->belongsTo(TenantLocation::class);
    }

    public function schedulable()
    {
        return $this->morphTo();
    }
}
