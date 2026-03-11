<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_event_deliveries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('integration_event_outbox_id')
                ->constrained('integration_event_outbox')
                ->cascadeOnDelete();
            $table->string('destination');
            $table->string('status');
            $table->unsignedInteger('attempts')->default(0);
            $table->timestampTz('next_retry_at')->nullable();
            $table->timestampTz('last_attempt_at')->nullable();
            $table->timestampTz('delivered_at')->nullable();
            $table->text('last_error')->nullable();
            $table->unsignedInteger('response_status_code')->nullable();
            $table->text('response_body')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('destination');
            $table->index('next_retry_at');
            $table->unique(['integration_event_outbox_id', 'destination'], 'integration_event_deliveries_outbox_destination_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_event_deliveries');
    }
};
