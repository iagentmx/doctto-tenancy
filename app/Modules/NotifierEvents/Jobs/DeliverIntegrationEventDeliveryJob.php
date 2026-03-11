<?php

namespace App\Modules\NotifierEvents\Jobs;

use App\Models\IntegrationEventDelivery;
use App\Modules\NotifierEvents\Services\DestinationPublisherRegistry;
use App\Repositories\Contracts\IntegrationEventDeliveryRepositoryInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class DeliverIntegrationEventDeliveryJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $deliveryId
    ) {}

    public function handle(
        IntegrationEventDeliveryRepositoryInterface $integrationEventDeliveryRepository,
        DestinationPublisherRegistry $destinationPublisherRegistry,
    ): void {
        $delivery = $integrationEventDeliveryRepository->claimPendingIntegrationEventDelivery($this->deliveryId);

        if (! $delivery || ! $delivery->outbox) {
            return;
        }

        $attempt = ((int) $delivery->attempts) + 1;

        try {
            $publisher = $destinationPublisherRegistry->resolve((string) $delivery->destination);
            $result = $publisher->publish($delivery->outbox);

            if ($result->successful) {
                $integrationEventDeliveryRepository->markIntegrationEventDeliveryAsDelivered($delivery->id, [
                    'attempts' => $attempt,
                    'last_attempt_at' => now()->utc(),
                    'response_status_code' => $result->statusCode,
                    'response_body' => $this->truncate($result->responseBody),
                ]);

                return;
            }

            $this->handleFailure(
                $integrationEventDeliveryRepository,
                $delivery,
                $attempt,
                sprintf(
                    'El destino [%s] respondió con estatus HTTP %d.',
                    $delivery->destination,
                    (int) $result->statusCode
                ),
                $result->statusCode,
                $result->responseBody,
            );
        } catch (Throwable $exception) {
            $this->handleFailure(
                $integrationEventDeliveryRepository,
                $delivery,
                $attempt,
                $exception->getMessage(),
                null,
                null,
            );
        }
    }

    private function handleFailure(
        IntegrationEventDeliveryRepositoryInterface $integrationEventDeliveryRepository,
        IntegrationEventDelivery $delivery,
        int $attempt,
        string $error,
        ?int $statusCode,
        ?string $responseBody,
    ): void {
        $retryAt = $this->resolveNextRetryAt((string) $delivery->destination, $attempt);

        $data = [
            'attempts' => $attempt,
            'last_attempt_at' => now()->utc(),
            'last_error' => $this->truncate($error),
            'response_status_code' => $statusCode,
            'response_body' => $this->truncate($responseBody),
        ];

        if ($retryAt === null) {
            $integrationEventDeliveryRepository->markIntegrationEventDeliveryAsFailed($delivery->id, $data);

            return;
        }

        $integrationEventDeliveryRepository->markIntegrationEventDeliveryForRetry($delivery->id, $data + [
            'next_retry_at' => $retryAt,
        ]);
    }

    private function resolveNextRetryAt(string $destination, int $attempt): ?string
    {
        $config = config("notifier_events.destinations.{$destination}", []);
        $maxAttempts = (int) ($config['max_attempts'] ?? 5);

        if ($attempt >= $maxAttempts) {
            return null;
        }

        $delays = $config['retry_delays_seconds'] ?? [60, 300, 900, 3600];
        $delay = (int) ($delays[$attempt - 1] ?? end($delays) ?: 3600);

        return now()->utc()->addSeconds($delay)->toIso8601String();
    }

    private function truncate(?string $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return mb_substr($value, 0, 8192);
    }
}
