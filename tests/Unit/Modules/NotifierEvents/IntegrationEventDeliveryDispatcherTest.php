<?php

namespace Tests\Unit\Modules\NotifierEvents;

use App\Models\IntegrationEventDelivery;
use App\Modules\NotifierEvents\Jobs\DeliverIntegrationEventDeliveryJob;
use App\Modules\NotifierEvents\Services\IntegrationEventDeliveryDispatcher;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use App\Repositories\Contracts\IntegrationEventOutboxRepositoryInterface;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class IntegrationEventDeliveryDispatcherTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_dispatch_pending_deliveries_dispatches_jobs_and_marks_outbox_as_dispatched(): void
    {
        Bus::fake();

        $deliveryOne = new IntegrationEventDelivery();
        $deliveryOne->id = 11;
        $deliveryOne->integration_event_outbox_id = 100;

        $deliveryTwo = new IntegrationEventDelivery();
        $deliveryTwo->id = 12;
        $deliveryTwo->integration_event_outbox_id = 101;

        $deliveryRepository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $deliveryRepository->shouldReceive('listPendingIntegrationEventDeliveriesReadyForDispatch')
            ->once()
            ->with('n8n', 5)
            ->andReturn(new Collection([$deliveryOne, $deliveryTwo]));

        $outboxRepository = Mockery::mock(IntegrationEventOutboxRepositoryInterface::class);
        $outboxRepository->shouldReceive('markIntegrationEventOutboxAsDispatched')->once()->with(100);
        $outboxRepository->shouldReceive('markIntegrationEventOutboxAsDispatched')->once()->with(101);

        $dispatcher = new IntegrationEventDeliveryDispatcher($deliveryRepository, $outboxRepository);

        $count = $dispatcher->dispatchPendingDeliveries('n8n', 5);

        $this->assertSame(2, $count);
        Bus::assertDispatched(DeliverIntegrationEventDeliveryJob::class, fn (DeliverIntegrationEventDeliveryJob $job) => $job->deliveryId === 11);
        Bus::assertDispatched(DeliverIntegrationEventDeliveryJob::class, fn (DeliverIntegrationEventDeliveryJob $job) => $job->deliveryId === 12);
    }

    public function test_requeue_failed_deliveries_uses_the_configured_default_limit(): void
    {
        config()->set('notifier_events.dispatch.limit', 25);

        $deliveryRepository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $deliveryRepository->shouldReceive('requeueFailedIntegrationEventDeliveries')
            ->once()
            ->with('n8n', 25)
            ->andReturn(3);

        $dispatcher = new IntegrationEventDeliveryDispatcher(
            $deliveryRepository,
            Mockery::mock(IntegrationEventOutboxRepositoryInterface::class),
        );

        $this->assertSame(3, $dispatcher->requeueFailedDeliveries('n8n'));
    }
}
