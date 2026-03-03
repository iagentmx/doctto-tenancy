<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\GetTenantByJidRequest;
use App\Http\Requests\GetTenantByEspoIdRequest;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use App\Exceptions\ApiServiceException;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\JsonResponse;

class TenantController extends Controller
{
    public function __construct(
        protected TenantEntitiesServiceInterface $tenantService
    ) {}

    /**
     * GET /api/v1/tenants/{tenantJid}
     */
    public function show(GetTenantByJidRequest $request, string $tenantJid): JsonResponse
    {
        try {
            $result = $this->tenantService->getByJid($tenantJid);

            return response()->json([
                'status' => 'success',
                'data'   => $result
            ], 200);
        } catch (ApiServiceException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'result'  => [],
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado obteniendo tenant por JID', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'jid'   => $tenantJid,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al obtener el tenant',
                'result'  => [],
            ], 500);
        }
    }

    /**
     * GET /api/v1/tenants/by-espocrm-id/{espocrmId}
     */
    public function showByEspoCrmId(GetTenantByEspoIdRequest $request, string $espocrmId): JsonResponse
    {
        try {
            $response = $this->tenantService->getByEspoCrmId($espocrmId);

            return response()->json($response, 200);
        } catch (ApiServiceException $e) {
            return response()->json([
                'status'  => 'error',
                'message' => $e->getMessage(),
                'result'  => [],
            ], $e->getStatusCode());
        } catch (\Throwable $e) {
            Log::error('❌ Error inesperado obteniendo tenant por EspoCRM ID', [
                'error'      => $e->getMessage(),
                'trace'      => $e->getTraceAsString(),
                'espocrm_id' => $espocrmId,
            ]);

            return response()->json([
                'status'  => 'error',
                'message' => 'Error interno al obtener el tenant',
                'result'  => [],
            ], 500);
        }
    }
}
