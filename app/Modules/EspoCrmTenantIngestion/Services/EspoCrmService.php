<?php

namespace App\Modules\EspoCrmTenantIngestion\Services;

use App\Exceptions\ApiServiceException;
use App\Exceptions\EspoCrmWebhookException;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmClientInterface;
use App\Modules\EspoCrmTenantIngestion\Contracts\EspoCrmServiceInterface;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertServiceCategoryUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertServiceUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertStaffUseCase;
use App\Modules\EspoCrmTenantIngestion\Services\UseCases\UpsertTenantFromAccountUseCase;
use App\Repositories\Contracts\TenantRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Throwable;

class EspoCrmService implements EspoCrmServiceInterface
{
    public function __construct(
        protected EspoCrmClientInterface $espoCrmClient,
        protected TenantRepositoryInterface $tenantRepository,

        // UseCases (aislar responsabilidades)
        protected UpsertTenantFromAccountUseCase $upsertTenantFromAccount,
        protected UpsertServiceCategoryUseCase $upsertServiceCategory,
        protected UpsertServiceUseCase $upsertService,
        protected UpsertStaffUseCase $upsertStaff,
    ) {}

    /**
     * Procesa el webhook de EspoCRM "account-updated".
     */
    public function handleAccountUpdated(array $payload): array
    {
        $espocrmId = $payload['id'] ?? null;

        if (!is_string($espocrmId) || trim($espocrmId) === '') {
            throw new EspoCrmWebhookException('Payload inválido: falta "id" para account-updated.', 422);
        }

        $espocrmId = trim($espocrmId);
        $tenant = $this->tenantRepository->findTenantByEspoCrmId($espocrmId);

        if (! $tenant) {
            return [
                'status'  => 'success',
                'message' => 'Tenant no existe para este account-updated. Evento ignorado.',
                'data'    => null,
            ];
        }

        try {
            $account = $this->espoCrmClient->getAccountById($espocrmId);
            $updatedTenant = $this->upsertTenantFromAccount->executeUpdateExisting($tenant->id, $account);

            return [
                'status'  => 'success',
                'message' => 'Tenant actualizado correctamente.',
                'data'    => $updatedTenant->toArray(),
            ];
        } catch (Throwable $e) {
            if ($e instanceof EspoCrmWebhookException) {
                throw $e;
            }

            if ($e instanceof ApiServiceException) {
                throw new EspoCrmWebhookException($e->getMessage(), $e->getStatusCode(), $e);
            }

            Log::error('❌ Error al procesar webhook EspoCRM (account-updated)', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw new EspoCrmWebhookException('Error al actualizar información del webhook.', 500, $e);
        }
    }

    /**
     * Procesa el webhook de EspoCRM "opportunity-updated".
     */
    public function handleOpportunityUpdated(array $payload): array
    {
        $opportunityId = $payload['id'] ?? null;
        $stage = $payload['stage'] ?? null;

        if (!is_string($opportunityId) || trim($opportunityId) === '') {
            throw new EspoCrmWebhookException('Payload inválido: falta "id" para opportunity-updated.', 422);
        }

        if (!is_string($stage) || trim($stage) === '') {
            throw new EspoCrmWebhookException('Payload inválido: falta "stage" para opportunity-updated.', 422);
        }

        if (trim($stage) !== 'Closed Won') {
            return [
                'status'  => 'success',
                'message' => 'Opportunity no está en stage "Closed Won". Evento ignorado.',
                'data'    => null,
            ];
        }

        try {
            $opportunity = $this->espoCrmClient->getOpportunityById(trim($opportunityId));
            $accountId = $opportunity['accountId'] ?? null;

            if (!is_string($accountId) || trim($accountId) === '') {
                throw new EspoCrmWebhookException('Opportunity inválida: falta "accountId".', 422);
            }

            $account = $this->espoCrmClient->getAccountById(trim($accountId));
            $tenant = $this->upsertTenantFromAccount->execute($account);

            return [
                'status'  => 'success',
                'message' => 'Tenant registrado o actualizado correctamente.',
                'data'    => $tenant->toArray(),
            ];
        } catch (Throwable $e) {
            if ($e instanceof EspoCrmWebhookException) {
                throw $e;
            }

            if ($e instanceof ApiServiceException) {
                throw new EspoCrmWebhookException($e->getMessage(), $e->getStatusCode(), $e);
            }

            Log::error('❌ Error al procesar webhook EspoCRM (opportunity-updated)', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw new EspoCrmWebhookException('Error al procesar opportunity-updated.', 500, $e);
        }
    }

    /**
     * Procesa el webhook de EspoCRM "service-created".
     */
    public function handleServiceCreated(array $payload): array
    {
        try {
            $tenant = $this->tenantRepository->findTenantByEspoCrmId($payload['accountId']);

            if (! $tenant) {
                throw new EspoCrmWebhookException('No se encontró el tenant para accountId: ' . $payload['accountId'], 404);
            }

            $category = $this->upsertServiceCategory->execute($tenant->id, $payload);

            $service = $this->upsertService->execute($tenant->id, $category->id, $payload);

            return [
                'status'  => 'success',
                'message' => 'Servicio registrado o actualizado correctamente.',
                'data'    => $service->toArray(),
            ];
        } catch (Throwable $e) {

            if ($e instanceof EspoCrmWebhookException) {
                throw $e;
            }

            Log::error('❌ Error al procesar webhook EspoCRM (service-created)', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw new EspoCrmWebhookException('Error al registrar servicio.', 500, $e);
        }
    }

    /**
     * Procesa el webhook de EspoCRM "service-updated".
     */
    public function handleServiceUpdated(array $payload): array
    {
        $espocrmId = $payload['id'] ?? null;

        if (!is_string($espocrmId) || trim($espocrmId) === '') {
            throw new EspoCrmWebhookException('Payload inválido: falta "id" para service-updated.', 422);
        }

        try {
            $service = $this->espoCrmClient->getServiceById(trim($espocrmId));

            return $this->handleServiceCreated($service);
        } catch (ApiServiceException $e) {
            throw new EspoCrmWebhookException($e->getMessage(), $e->getStatusCode(), $e);
        }
    }

    /**
     * Procesa el webhook de EspoCRM "staff-created".
     */
    public function handleStaffCreated(array $payload): array
    {
        try {
            $tenant = $this->tenantRepository->findTenantByEspoCrmId($payload['accountId']);

            if (! $tenant) {
                throw new EspoCrmWebhookException('No se encontró tenant para accountId: ' . $payload['accountId'], 404);
            }

            $staff = $this->upsertStaff->execute($tenant, $payload);

            return [
                'status'  => 'success',
                'message' => 'Staff registrado correctamente.',
                'data'    => $staff->toArray(),
            ];
        } catch (Throwable $e) {

            if ($e instanceof EspoCrmWebhookException) {
                throw $e;
            }

            Log::error('❌ Error en webhook EspoCRM (staff-created)', [
                'error'   => $e->getMessage(),
                'payload' => $payload,
            ]);

            throw new EspoCrmWebhookException('Error al registrar staff.', 500, $e);
        }
    }

    /**
     * Procesa el webhook de EspoCRM "staff-updated".
     */
    public function handleStaffUpdated(array $payload): array
    {
        $espocrmId = $payload['id'] ?? null;

        if (!is_string($espocrmId) || trim($espocrmId) === '') {
            throw new EspoCrmWebhookException('Payload inválido: falta "id" para staff-updated.', 422);
        }

        try {
            $staff = $this->espoCrmClient->getStaffById(trim($espocrmId));

            return $this->handleStaffCreated($staff);
        } catch (ApiServiceException $e) {
            throw new EspoCrmWebhookException($e->getMessage(), $e->getStatusCode(), $e);
        }
    }
}
