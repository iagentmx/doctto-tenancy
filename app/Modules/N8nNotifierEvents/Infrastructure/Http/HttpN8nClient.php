<?php

namespace App\Modules\N8nNotifierEvents\Infrastructure\Http;

use App\Exceptions\ApiServiceException;
use App\Modules\N8nNotifierEvents\Contracts\N8nClientInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class HttpN8nClient implements N8nClientInterface
{
    public function postUpdateTenantWebhook(array $payload): void
    {
        $url = config('n8n.webhook.update_tenant');
        $apiKey = config('n8n.api_key');

        if (!is_string($url) || trim($url) === '') {
            throw new ApiServiceException('La url del webhook de n8n no está configurado', 500);
        }

        if (!is_string($apiKey) || trim($apiKey) === '') {
            throw new ApiServiceException('n8n.api_key no está configurado', 500);
        }

        try {
            $resp = $this->request()
                ->withHeaders(['x-api-key' => trim($apiKey)])
                ->post(trim($url), $payload);
        } catch (ConnectionException $e) {
            throw new ApiServiceException('No se pudo conectar a n8n (webhook update tenant)', 502, $e);
        }

        if (! $resp->successful()) {
            Log::error('❌ n8n webhook error (update tenant)', [
                'status'  => $resp->status(),
                'body'    => $resp->body(),
                'payload' => $payload,
            ]);

            throw new ApiServiceException('n8n devolvió un error al recibir el webhook (update tenant)', 502);
        }
    }

    private function request(): PendingRequest
    {
        return Http::acceptJson()
            ->asJson()
            ->connectTimeout((int) config('n8n.connect_timeout', 5))
            ->timeout((int) config('n8n.timeout', 10));
    }
}
