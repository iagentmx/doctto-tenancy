<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_event_outbox', function (Blueprint $table): void {
            $table->id();
            $table->uuid('event_uuid')->unique();
            $table->string('event_name');
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id');
            $table->jsonb('payload');
            $table->timestampTz('occurred_at');
            $table->string('correlation_id')->nullable();
            $table->string('source')->nullable();
            $table->timestampTz('dispatched_at')->nullable();
            $table->timestamps();

            $table->index(['event_name', 'tenant_id']);
            $table->index(['entity_type', 'entity_id']);
            $table->index('occurred_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_event_outbox');
    }
};
