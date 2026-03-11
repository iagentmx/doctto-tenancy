<?php

namespace App\Models;

use App\Enums\TenantAdminChannelType;
use App\Enums\TenantAdminRole;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TenantAdmin extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'channel_type',
        'jid',
        'role',
        'is_active',
        'settings',
    ];

    protected $casts = [
        'channel_type' => TenantAdminChannelType::class,
        'role' => TenantAdminRole::class,
        'is_active' => 'boolean',
        'settings' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
