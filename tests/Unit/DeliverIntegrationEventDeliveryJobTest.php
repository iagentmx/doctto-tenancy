<?php

namespace Tests\Unit;

use App\Models\IntegrationEventDelivery;
use App\Models\IntegrationEventOutbox;
use App\Modules\NotifierEvents\Contracts\DestinationPublisherInterface;
use App\Modules\NotifierEvents\DTO\PublishResult;
use App\Modules\NotifierEvents\Jobs\DeliverIntegrationEventDeliveryJob;
use App\Modules\NotifierEvents\Services\DestinationPublisherRegistry;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DeliverIntegrationEventDeliveryJobTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_marks_a_delivery_as_delivered_when_the_publisher_succeeds(): void
    {
        $repository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $registry = Mockery::mock(DestinationPublisherRegistry::class);
        $publisher = Mockery::mock(DestinationPublisherInterface::class);

        $delivery = $this->makeDelivery(attempts: 1, destination: 'n8n');

        $repository->shouldReceive('claimPendingIntegrationEventDelivery')
            ->once()
            ->with(55)
            ->andReturn($delivery);

        $registry->shouldReceive('resolve')
            ->once()
            ->with('n8n')
            ->andReturn($publisher);

        $publisher->shouldReceive('publish')
            ->once()
            ->with($delivery->outbox)
            ->andReturn(new PublishResult(
                successful: true,
                statusCode: 202,
                responseBody: str_repeat('x', 9000),
            ));

        $repository->shouldReceive('markIntegrationEventDeliveryAsDelivered')
            ->once()
            ->with(55, Mockery::on(function (array $data): bool {
                return $data['attempts'] === 2
                    && $data['response_status_code'] === 202
                    && mb_strlen($data['response_body']) === 8192
                    && $data['last_attempt_at'] !== null;
            }));

        $job = new DeliverIntegrationEventDeliveryJob(55);
        $job->handle($repository, $registry);

        $this->addToAssertionCount(1);
    }

    public function test_it_marks_a_delivery_for_retry_when_the_publisher_returns_an_unsuccessful_response(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'max_attempts' => 3,
            'retry_delays_seconds' => [60, 300],
        ]);

        $repository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $registry = Mockery::mock(DestinationPublisherRegistry::class);
        $publisher = Mockery::mock(DestinationPublisherInterface::class);

        $delivery = $this->makeDelivery(attempts: 0, destination: 'n8n');

        $repository->shouldReceive('claimPendingIntegrationEventDelivery')
            ->once()
            ->with(55)
            ->andReturn($delivery);

        $registry->shouldReceive('resolve')
            ->once()
            ->with('n8n')
            ->andReturn($publisher);

        $publisher->shouldReceive('publish')
            ->once()
            ->andReturn(new PublishResult(
                successful: false,
                statusCode: 500,
                responseBody: 'remote error',
            ));

        $repository->shouldReceive('markIntegrationEventDeliveryForRetry')
            ->once()
            ->with(55, Mockery::on(function (array $data): bool {
                return $data['attempts'] === 1
                    && $data['response_status_code'] === 500
                    && $data['response_body'] === 'remote error'
                    && $data['last_error'] === 'El destino [n8n] respondió con estatus HTTP 500.'
                    && is_string($data['next_retry_at'])
                    && $data['next_retry_at'] !== '';
            }));

        $job = new DeliverIntegrationEventDeliveryJob(55);
        $job->handle($repository, $registry);

        $this->addToAssertionCount(1);
    }

    public function test_it_marks_a_delivery_as_failed_when_the_exception_reaches_the_max_attempts(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'max_attempts' => 1,
            'retry_delays_seconds' => [60],
        ]);

        $repository = Mockery::mock(IntegrationEventDeliveryRepositoryInterface::class);
        $registry = Mockery::mock(DestinationPublisherRegistry::class);
        $publisher = Mockery::mock(DestinationPublisherInterface::class);

        $delivery = $this->makeDelivery(attempts: 0, destination: 'n8n');

        $repository->shouldReceive('claimPendingIntegrationEventDelivery')
            ->once()
            ->with(55)
            ->andReturn($delivery);

        $registry->shouldReceive('resolve')
            ->once()
            ->with('n8n')
            ->andReturn($publisher);

        $publisher->shouldReceive('publish')
            ->once()
            ->andThrow(new RuntimeException('n8n offline'));

        $repository->shouldReceive('markIntegrationEventDeliveryAsFailed')
            ->once()
            ->with(55, Mockery::on(function (array $data): bool {
                return $data['attempts'] === 1
                    && $data['response_status_code'] === null
                    && $data['response_body'] === null
                    && $data['last_error'] === 'n8n offline'
                    && $data['last_attempt_at'] !== null;
            }));

        $job = new DeliverIntegrationEventDeliveryJob(55);
        $job->handle($repository, $registry);

        $this->addToAssertionCount(1);
    }

    private function makeDelivery(int $attempts, string $destination): IntegrationEventDelivery
    {
        $outbox = new IntegrationEventOutbox();
        $outbox->id = 88;
        $outbox->event_uuid = 'evt-123';
        $outbox->payload = ['event' => 'service.updated'];

        $delivery = new IntegrationEventDelivery();
        $delivery->id = 55;
        $delivery->attempts = $attempts;
        $delivery->destination = $destination;
        $delivery->setRelation('outbox', $outbox);

        return $delivery;
    }
}
