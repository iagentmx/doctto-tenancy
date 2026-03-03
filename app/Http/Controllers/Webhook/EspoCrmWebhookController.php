<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\EspoCrmWebhookException;
use App\Http\Controllers\Controller;
use App\Http\Requests\EspoCrmOpportunityUpdatedRequest;
use App\Http\Requests\EspoCrmServiceCreatedRequest;
use App\Http\Requests\EspoCrmStaffCreatedRequest;
use App\Http\Requests\EspoCrmUpdatedRequest;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmServiceInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class EspoCrmWebhookController extends Controller
{
    public function __construct(
        protected EspoCrmServiceInterface $service
    ) {}

    /**
     * Webhook: espocrm/account-updated
     */
    public function accountUpdated(EspoCrmUpdatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // EspoCRM a veces envía: [{...}]
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleAccountUpdated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (account-updated)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }

    /**
     * Webhook: espocrm/opportunity-updated
     */
    public function opportunityUpdated(EspoCrmOpportunityUpdatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // EspoCRM a veces envía: [{...}]
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleOpportunityUpdated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (opportunity-updated)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }

    /**
     * Webhook: espocrm/service-created
     */
    public function serviceCreated(EspoCrmServiceCreatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // EspoCRM a veces envía: [{...}]
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleServiceCreated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (service-created)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }

    /**
     * Webhook: espocrm/service-updated
     */
    public function serviceUpdated(EspoCrmUpdatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // EspoCRM a veces envía: [{...}]
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleServiceUpdated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (service-updated)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }

    /**
     * Webhook: espocrm/staff-created
     */
    public function staffCreated(EspoCrmStaffCreatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // Si viene como array → tomar el primer elemento
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleStaffCreated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (staff-created)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }

    /**
     * Webhook: espocrm/staff-updated
     */
    public function staffUpdated(EspoCrmUpdatedRequest $request): JsonResponse
    {
        $payload = $request->validated();

        // EspoCRM a veces envía: [{...}]
        if (isset($payload[0]) && is_array($payload[0])) {
            $payload = $payload[0];
        }

        try {
            $result = $this->service->handleStaffUpdated($payload);
            return response()->json($result, 200);
        } catch (EspoCrmWebhookException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado procesando webhook de EspoCRM (staff-updated)', [
                'error'   => $e->getMessage(),
                'trace'   => $e->getTraceAsString(),
                'payload' => $payload,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al procesar el webhook de EspoCRM',
            ], 500);
        }
    }
}
