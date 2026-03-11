<?php

namespace Tests\Feature;

use App\Exceptions\ApiServiceException;
use App\Modules\TenantEntities\Contracts\TenantEntitiesServiceInterface;
use Mockery;
use Tests\TestCase;

class TenantCatalogEndpointTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_returns_the_tenant_catalog(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('getCatalogByTenantId')
            ->once()
            ->with(15)
            ->andReturn([
                'tenant' => [
                    'id' => 15,
                    'name' => 'Tenant Demo',
                ],
                'locations' => [],
                'staff' => [],
                'services' => [],
                'service_categories' => [],
                'tenant_admins' => [],
                'resources' => [],
                'schedules' => [],
            ]);

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->getJson('/api/v1/tenants/15/catalog');

        $response->assertOk()
            ->assertJson([
                'status' => 'success',
                'data' => [
                    'tenant' => [
                        'id' => 15,
                        'name' => 'Tenant Demo',
                    ],
                ],
            ]);
    }

    public function test_it_returns_404_when_catalog_tenant_does_not_exist(): void
    {
        $service = Mockery::mock(TenantEntitiesServiceInterface::class);
        $service->shouldReceive('getCatalogByTenantId')
            ->once()
            ->with(404)
            ->andThrow(new ApiServiceException('Tenant no encontrado', 404));

        $this->app->instance(TenantEntitiesServiceInterface::class, $service);

        $response = $this->withHeaders([
            'X-Api-Token' => env('APP_API_TOKEN'),
        ])->getJson('/api/v1/tenants/404/catalog');

        $response->assertStatus(404)
            ->assertJson([
                'status' => 'error',
                'message' => 'Tenant no encontrado',
                'result' => [],
            ]);
    }
}
