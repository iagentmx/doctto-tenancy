<?php

namespace Tests\Feature;

use App\Exceptions\ApiServiceException;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use Mockery;
use Tests\TestCase;

class TenantStaffEndpointsTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_lists_staff_for_a_tenant(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('listStaffByTenantJid')
            ->once()
            ->with('5217711986426@s.whatsapp.net')
            ->andReturn([
                [
                    'id' => 4,
                    'tenant_id' => 20,
                    'name' => 'Dra. Perez',
                    'role' => 'doctor',
                    'service_ids' => [3],
                ],
            ]);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->getJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    [
                        'id' => 4,
                        'role' => 'doctor',
                    ],
                ],
            ]);
    }

    public function test_it_returns_staff_detail(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('getStaffByTenantJidAndId')
            ->once()
            ->with('5217711986426@s.whatsapp.net', 4)
            ->andReturn([
                'id' => 4,
                'tenant_id' => 20,
                'name' => 'Dra. Perez',
                'role' => 'doctor',
                'settings' => [
                    'about' => 'Perfil',
                    'specialty' => 'Ortodoncia',
                ],
                'service_ids' => [3],
            ]);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->getJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff/4');

        $response->assertOk()
            ->assertJsonPath('data.settings.specialty', 'Ortodoncia');
    }

    public function test_it_creates_staff(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('createStaff')
            ->once()
            ->withArgs(function (string $tenantJid, $staffData): bool {
                return $tenantJid === '5217711986426@s.whatsapp.net'
                    && $staffData->name === 'Dra. Perez'
                    && $staffData->tenantId === 0;
            })
            ->andReturn([
                'id' => 4,
                'tenant_id' => 20,
                'name' => 'Dra. Perez',
                'role' => 'doctor',
                'is_active' => true,
                'settings' => [
                    'about' => 'Perfil',
                    'specialty' => 'Ortodoncia',
                ],
                'service_ids' => [],
            ]);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->postJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff', [
            'name' => 'Dra. Perez',
            'role' => 'doctor',
            'phone' => '7710000000',
            'email' => 'dra@test.mx',
            'is_active' => true,
            'settings' => [
                'about' => 'Perfil',
                'specialty' => 'Ortodoncia',
            ],
        ]);

        $response->assertCreated()
            ->assertJsonPath('data.name', 'Dra. Perez');
    }

    public function test_it_updates_staff(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('updateStaff')
            ->once()
            ->withArgs(function (string $tenantJid, int $staffId, $staffData): bool {
                return $tenantJid === '5217711986426@s.whatsapp.net'
                    && $staffId === 4
                    && $staffData->role === 'doctor';
            })
            ->andReturn([
                'id' => 4,
                'tenant_id' => 20,
                'name' => 'Dra. Ana',
                'role' => 'doctor',
                'is_active' => false,
                'settings' => [
                    'about' => 'Actualizado',
                    'specialty' => 'Ortodoncia',
                ],
                'service_ids' => [3],
            ]);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->patchJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff/4', [
            'name' => 'Dra. Ana',
            'role' => 'doctor',
            'phone' => '7710000000',
            'email' => 'dra@test.mx',
            'is_active' => false,
            'settings' => [
                'about' => 'Actualizado',
                'specialty' => 'Ortodoncia',
            ],
        ]);

        $response->assertOk()
            ->assertJsonPath('data.is_active', false);
    }

    public function test_it_deletes_staff(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('deleteStaff')
            ->once()
            ->with('5217711986426@s.whatsapp.net', 4);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->deleteJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff/4');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [],
            ]);
    }

    public function test_it_returns_404_when_staff_does_not_belong_to_tenant(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('getStaffByTenantJidAndId')
            ->once()
            ->with('5217711986426@s.whatsapp.net', 99)
            ->andThrow(new ApiServiceException('Staff no encontrado', 404));

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->getJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff/99');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Staff no encontrado',
                'result' => [],
            ]);
    }

    public function test_it_rejects_invalid_staff_payload(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->postJson('/api/v1/tenants/5217711986426@s.whatsapp.net/staff', [
            'name' => '',
            'role' => 'invalid',
            'email' => 'correo-invalido',
            'is_active' => 'no-bool',
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'role', 'email', 'is_active']);
    }
}
