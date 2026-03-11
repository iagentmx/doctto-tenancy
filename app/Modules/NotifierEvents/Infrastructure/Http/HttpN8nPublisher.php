<?php

namespace App\Modules\NotifierEvents\Infrastructure\Http;

use App\Exceptions\ApiServiceException;
use App\Models\IntegrationEventOutbox;
use App\Modules\NotifierEvents\Contracts\DestinationPublisherInterface;
use App\Modules\NotifierEvents\DTO\PublishResult;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class HttpN8nPublisher implements DestinationPublisherInterface
{
    public function destination(): string
    {
        return 'n8n';
    }

    public function publish(IntegrationEventOutbox $event): PublishResult
    {
        $config = config('notifier_events.destinations.n8n', []);
        $url = $config['webhook_url'] ?? null;
        $apiKey = $config['api_key'] ?? null;

        if (! is_string($url) || trim($url) === '') {
            throw new ApiServiceException('La URL del destino n8n no está configurada.', 500);
        }

        if (! is_string($apiKey) || trim($apiKey) === '') {
            throw new ApiServiceException('La api key del destino n8n no está configurada.', 500);
        }

        try {
            $response = $this->request($config)
                ->withHeaders([
                    'x-api-key' => trim($apiKey),
                    'X-Event-Id' => (string) $event->event_uuid,
                ])
                ->post(trim($url), $event->payload ?? []);
        } catch (ConnectionException $exception) {
            throw new ApiServiceException('No se pudo conectar al destino n8n.', 502, $exception);
        }

        return new PublishResult(
            successful: $response->successful(),
            statusCode: $response->status(),
            responseBody: $response->body(),
        );
    }

    private function request(array $config): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) ($config['connect_timeout'] ?? 5))
            ->timeout((int) ($config['timeout'] ?? 10));
    }
}
