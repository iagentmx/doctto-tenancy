<?php

use App\Modules\NotifierEvents\Services\IntegrationEventDeliveryDispatcher;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('notifier-events:dispatch-pending {--destination=} {--limit=100}', function (): void {
    $dispatcher = app(IntegrationEventDeliveryDispatcher::class);

    $count = $dispatcher->dispatchPendingDeliveries(
        $this->option('destination'),
        (int) $this->option('limit'),
    );

    $this->info("Deliveries encoladas: {$count}");
})->purpose('Encola deliveries pendientes o con retry vencido del outbox de eventos de integración.');

Artisan::command('notifier-events:retry-deliveries {--destination=} {--limit=100}', function (): void {
    $dispatcher = app(IntegrationEventDeliveryDispatcher::class);

    $requeued = $dispatcher->requeueFailedDeliveries(
        $this->option('destination'),
        (int) $this->option('limit'),
    );

    $dispatched = $dispatcher->dispatchPendingDeliveries(
        $this->option('destination'),
        (int) $this->option('limit'),
    );

    $this->info("Deliveries fallidas reencoladas: {$requeued}");
    $this->info("Deliveries encoladas: {$dispatched}");
})->purpose('Relanza deliveries fallidas y pendientes vencidas del outbox de eventos de integración.');

Schedule::command('notifier-events:dispatch-pending --limit=100')->everyMinute();
