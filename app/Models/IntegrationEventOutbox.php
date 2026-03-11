<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IntegrationEventOutbox extends Model
{
    use HasFactory;

    protected $table = 'integration_event_outbox';

    protected $fillable = [
        'event_uuid',
        'event_name',
        'tenant_id',
        'entity_type',
        'entity_id',
        'payload',
        'occurred_at',
        'correlation_id',
        'source',
        'dispatched_at',
    ];

    protected $casts = [
        'payload' => 'array',
        'occurred_at' => 'datetime',
        'dispatched_at' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(IntegrationEventDelivery::class, 'integration_event_outbox_id');
    }
}
