<?php

namespace Tests\Unit\NotifierEvents;

use App\Exceptions\ApiServiceException;
use App\Models\IntegrationEventOutbox;
use App\Modules\NotifierEvents\Infrastructure\Http\HttpN8nPublisher;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class HttpN8nPublisherTest extends TestCase
{
    public function test_it_requires_a_webhook_url(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'api_key' => 'secret',
        ]);

        $publisher = new HttpN8nPublisher();

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('La URL del destino n8n no está configurada.');

        $publisher->publish($this->makeOutbox());
    }

    public function test_it_requires_an_api_key(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'webhook_url' => 'https://n8n.test/webhook',
        ]);

        $publisher = new HttpN8nPublisher();

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('La api key del destino n8n no está configurada.');

        $publisher->publish($this->makeOutbox());
    }

    public function test_it_sends_the_expected_headers_and_returns_the_publish_result(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'webhook_url' => 'https://n8n.test/webhook',
            'api_key' => 'secret',
        ]);

        Http::fake(function (Request $request) {
            $this->assertSame('secret', $request->header('x-api-key')[0] ?? null);
            $this->assertSame('evt-123', $request->header('X-Event-Id')[0] ?? null);
            $this->assertSame(['event' => 'service.updated'], $request->data());

            return Http::response('accepted', 202);
        });

        $result = (new HttpN8nPublisher())->publish($this->makeOutbox());

        $this->assertTrue($result->successful);
        $this->assertSame(202, $result->statusCode);
        $this->assertSame('accepted', $result->responseBody);
    }

    public function test_it_remaps_connection_exceptions(): void
    {
        config()->set('notifier_events.destinations.n8n', [
            'webhook_url' => 'https://n8n.test/webhook',
            'api_key' => 'secret',
        ]);

        Http::fake(fn () => throw new ConnectionException('offline'));

        $publisher = new HttpN8nPublisher();

        try {
            $publisher->publish($this->makeOutbox());
            $this->fail('Se esperaba una excepción.');
        } catch (ApiServiceException $exception) {
            $this->assertSame('No se pudo conectar al destino n8n.', $exception->getMessage());
            $this->assertSame(502, $exception->getStatusCode());
        }
    }

    private function makeOutbox(): IntegrationEventOutbox
    {
        $outbox = new IntegrationEventOutbox();
        $outbox->event_uuid = 'evt-123';
        $outbox->payload = ['event' => 'service.updated'];

        return $outbox;
    }
}
