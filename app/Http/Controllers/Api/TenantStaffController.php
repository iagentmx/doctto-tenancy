<?php

namespace App\Http\Controllers\Api;

use App\Exceptions\ApiServiceException;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeleteStaffRequest;
use App\Http\Requests\ListStaffRequest;
use App\Http\Requests\ShowStaffRequest;
use App\Http\Requests\StoreStaffRequest;
use App\Http\Requests\UpdateStaffRequest;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use App\Modules\TenantEntities\DTO\StaffData;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class TenantStaffController extends Controller
{
    public function __construct(
        protected TenantEntitiesServiceInterface $tenantEntitiesService,
    ) {}

    public function index(ListStaffRequest $request, string $tenantJid): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->tenantEntitiesService->listStaffByTenantJid($tenantJid),
            ]);
        } catch (ApiServiceException $e) {
            return $this->errorResponse($e);
        } catch (\Throwable $e) {
            $this->logUnexpectedError('listando staff del tenant', $e, [
                'tenant_jid' => $tenantJid,
            ]);

            return $this->unexpectedErrorResponse('Error interno al listar el staff del tenant');
        }
    }

    public function show(ShowStaffRequest $request, string $tenantJid, int $staffId): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->tenantEntitiesService->getStaffByTenantJidAndId($tenantJid, $staffId),
            ]);
        } catch (ApiServiceException $e) {
            return $this->errorResponse($e);
        } catch (\Throwable $e) {
            $this->logUnexpectedError('obteniendo staff del tenant', $e, [
                'tenant_jid' => $tenantJid,
                'staff_id' => $staffId,
            ]);

            return $this->unexpectedErrorResponse('Error interno al obtener el staff del tenant');
        }
    }

    public function store(StoreStaffRequest $request, string $tenantJid): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->tenantEntitiesService->createStaff(
                    $tenantJid,
                    $this->makeStaffData($request->validated())
                ),
            ], 201);
        } catch (ApiServiceException $e) {
            return $this->errorResponse($e);
        } catch (\Throwable $e) {
            $this->logUnexpectedError('creando staff del tenant', $e, [
                'tenant_jid' => $tenantJid,
            ]);

            return $this->unexpectedErrorResponse('Error interno al crear el staff del tenant');
        }
    }

    public function update(UpdateStaffRequest $request, string $tenantJid, int $staffId): JsonResponse
    {
        try {
            return response()->json([
                'status' => 'success',
                'data' => $this->tenantEntitiesService->updateStaff(
                    $tenantJid,
                    $staffId,
                    $this->makeStaffData($request->validated())
                ),
            ]);
        } catch (ApiServiceException $e) {
            return $this->errorResponse($e);
        } catch (\Throwable $e) {
            $this->logUnexpectedError('actualizando staff del tenant', $e, [
                'tenant_jid' => $tenantJid,
                'staff_id' => $staffId,
            ]);

            return $this->unexpectedErrorResponse('Error interno al actualizar el staff del tenant');
        }
    }

    public function destroy(DeleteStaffRequest $request, string $tenantJid, int $staffId): JsonResponse
    {
        try {
            $this->tenantEntitiesService->deleteStaff($tenantJid, $staffId);

            return response()->json([
                'status' => 'success',
                'data' => [],
            ]);
        } catch (ApiServiceException $e) {
            return $this->errorResponse($e);
        } catch (\Throwable $e) {
            $this->logUnexpectedError('eliminando staff del tenant', $e, [
                'tenant_jid' => $tenantJid,
                'staff_id' => $staffId,
            ]);

            return $this->unexpectedErrorResponse('Error interno al eliminar el staff del tenant');
        }
    }

    private function makeStaffData(array $validated): StaffData
    {
        return new StaffData(
            tenantId: 0,
            name: $validated['name'],
            role: $validated['role'],
            phone: $validated['phone'] ?? null,
            email: $validated['email'] ?? null,
            isActive: $validated['is_active'],
            about: $validated['settings']['about'] ?? null,
            specialty: $validated['settings']['specialty'] ?? null,
        );
    }

    private function errorResponse(ApiServiceException $exception): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $exception->getMessage(),
            'result' => [],
        ], $exception->getStatusCode());
    }

    private function unexpectedErrorResponse(string $message): JsonResponse
    {
        return response()->json([
            'status' => 'error',
            'message' => $message,
            'result' => [],
        ], 500);
    }

    private function logUnexpectedError(string $action, \Throwable $exception, array $context): void
    {
        Log::error("❌ Error inesperado {$action}", array_merge($context, [
            'error' => $exception->getMessage(),
            'trace' => $exception->getTraceAsString(),
        ]));
    }
}
