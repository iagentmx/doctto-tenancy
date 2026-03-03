<?php

namespace App\Modules\EspoCrmTenantIngestion\Infrastructure\Http;

use App\Exceptions\ApiServiceException;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmClientInterface;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmConfigProviderInterface;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;

class HttpEspoCrmClient implements EspoCrmClientInterface
{
    public function __construct(
        protected EspoCrmConfigProviderInterface $config
    ) {}

    public function getAccountById(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new ApiServiceException('EspoCRM Account id es requerido', 422);
        }

        try {
            $resp = $this->request()->get("/api/v1/Account/{$id}");
        } catch (ConnectionException $e) {
            throw new ApiServiceException('No se pudo conectar con EspoCRM', 502, $e);
        }

        if (! $resp->successful()) {
            $msg = $this->extractErrorMessage($resp->json());
            throw new ApiServiceException("EspoCRM Account GET error: {$msg}", $resp->status());
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new ApiServiceException('EspoCRM devolvió una respuesta inválida', 502);
        }

        return $json;
    }

    public function getOpportunityById(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new ApiServiceException('EspoCRM Opportunity id es requerido', 422);
        }

        try {
            $resp = $this->request()->get("/api/v1/Opportunity/{$id}");
        } catch (ConnectionException $e) {
            throw new ApiServiceException('No se pudo conectar con EspoCRM', 502, $e);
        }

        if (! $resp->successful()) {
            $msg = $this->extractErrorMessage($resp->json());
            throw new ApiServiceException("EspoCRM Opportunity GET error: {$msg}", $resp->status());
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new ApiServiceException('EspoCRM devolvió una respuesta inválida', 502);
        }

        return $json;
    }

    public function getServiceById(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new ApiServiceException('EspoCRM Service id es requerido', 422);
        }

        try {
            $resp = $this->request()->get("/api/v1/CService/{$id}");
        } catch (ConnectionException $e) {
            throw new ApiServiceException('No se pudo conectar con EspoCRM', 502, $e);
        }

        if (! $resp->successful()) {
            $msg = $this->extractErrorMessage($resp->json());
            throw new ApiServiceException("EspoCRM Service GET error: {$msg}", $resp->status());
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new ApiServiceException('EspoCRM devolvió una respuesta inválida', 502);
        }

        return $json;
    }

    public function getStaffById(string $id): array
    {
        $id = trim($id);
        if ($id === '') {
            throw new ApiServiceException('EspoCRM Staff id es requerido', 422);
        }

        try {
            $resp = $this->request()->get("/api/v1/CStaff/{$id}");
        } catch (ConnectionException $e) {
            throw new ApiServiceException('No se pudo conectar con EspoCRM', 502, $e);
        }

        if (! $resp->successful()) {
            $msg = $this->extractErrorMessage($resp->json());
            throw new ApiServiceException("EspoCRM Staff GET error: {$msg}", $resp->status());
        }

        $json = $resp->json();
        if (! is_array($json)) {
            throw new ApiServiceException('EspoCRM devolvió una respuesta inválida', 502);
        }

        return $json;
    }

    private function request(): PendingRequest
    {
        $timeoutSeconds = (int) $this->config->timeoutSeconds();

        $baseUrl  = (string) $this->config->baseUrl();
        $username = (string) $this->config->username();
        $password = (string) $this->config->password();

        if (trim($baseUrl) === '') {
            throw new ApiServiceException('espocrm.base_url no está configurado', 500);
        }
        if (trim($username) === '' || trim($password) === '') {
            throw new ApiServiceException('Credenciales de EspoCRM no configuradas (espocrm.username / espocrm.password)', 500);
        }

        return Http::baseUrl($baseUrl)
            ->timeout($timeoutSeconds)
            ->withBasicAuth($username, $password)
            ->withHeaders([
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ]);
    }

    private function extractErrorMessage($json): string
    {
        if (is_array($json)) {
            $msg = $json['message'] ?? null;
            if (is_string($msg) && trim($msg) !== '') {
                return trim($msg);
            }

            $error = $json['error'] ?? null;
            if (is_string($error) && trim($error) !== '') {
                return trim($error);
            }
        }

        return 'Error desconocido';
    }
}
