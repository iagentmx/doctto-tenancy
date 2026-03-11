<?php

namespace App\Models;

use App\Enums\IntegrationEventDeliveryStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IntegrationEventDelivery extends Model
{
    use HasFactory;

    protected $table = 'integration_event_deliveries';

    protected $fillable = [
        'integration_event_outbox_id',
        'destination',
        'status',
        'attempts',
        'next_retry_at',
        'last_attempt_at',
        'delivered_at',
        'last_error',
        'response_status_code',
        'response_body',
    ];

    protected $casts = [
        'status' => IntegrationEventDeliveryStatus::class,
        'next_retry_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'delivered_at' => 'datetime',
        'attempts' => 'integer',
        'response_status_code' => 'integer',
    ];

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(IntegrationEventOutbox::class, 'integration_event_outbox_id');
    }
}
