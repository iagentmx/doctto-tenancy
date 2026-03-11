<?php

namespace Tests\Unit;

use App\Enums\TenantAdminRole;
use App\Exceptions\ApiServiceException;
use App\Models\TenantAdmin;
use App\Modules\TenantEntities\DTO\TenantAdminData;
use App\Modules\TenantEntities\UseCases\TenantAdmins\RegisterTenantAdmin;
use App\Repositories\Contracts\TenantAdminRepositoryInterface;
use Mockery;
use Tests\TestCase;

class RegisterTenantAdminTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_first_registered_tenant_admin_is_forced_as_owner(): void
    {
        $repository = Mockery::mock(TenantAdminRepositoryInterface::class);
        $useCase = new RegisterTenantAdmin($repository);
        $tenantAdminData = new TenantAdminData(
            tenantId: 15,
            channelType: 'whatsapp',
            jid: '5217711986426@s.whatsapp.net',
            role: TenantAdminRole::Admin->value,
        );

        $createdTenantAdmin = new TenantAdmin([
            'tenant_id' => 15,
            'channel_type' => 'whatsapp',
            'jid' => '5217711986426@s.whatsapp.net',
            'role' => TenantAdminRole::Owner->value,
            'is_active' => true,
            'settings' => [],
        ]);

        $repository->shouldReceive('findTenantAdminByTenantChannelTypeAndJid')
            ->once()
            ->with(15, 'whatsapp', '5217711986426@s.whatsapp.net')
            ->andReturn(null);

        $repository->shouldReceive('findOwnerTenantAdminByTenantId')
            ->once()
            ->with(15)
            ->andReturn(null);

        $repository->shouldReceive('updateOrCreateTenantAdmin')
            ->once()
            ->with(
                [
                    'tenant_id' => 15,
                    'channel_type' => 'whatsapp',
                    'jid' => '5217711986426@s.whatsapp.net',
                ],
                [
                    'tenant_id' => 15,
                    'channel_type' => 'whatsapp',
                    'jid' => '5217711986426@s.whatsapp.net',
                    'role' => TenantAdminRole::Owner->value,
                    'is_active' => true,
                    'settings' => [],
                ],
            )
            ->andReturn($createdTenantAdmin);

        $result = $useCase->execute($tenantAdminData);

        $this->assertSame(TenantAdminRole::Owner, $result->role);
    }

    public function test_it_rejects_registering_a_second_owner_for_the_same_tenant(): void
    {
        $repository = Mockery::mock(TenantAdminRepositoryInterface::class);
        $useCase = new RegisterTenantAdmin($repository);
        $tenantAdminData = new TenantAdminData(
            tenantId: 15,
            channelType: 'email',
            jid: 'owner2@demo.test',
            role: TenantAdminRole::Owner->value,
        );

        $currentOwner = new TenantAdmin([
            'id' => 10,
            'tenant_id' => 15,
            'channel_type' => 'whatsapp',
            'jid' => '5217711986426@s.whatsapp.net',
            'role' => TenantAdminRole::Owner->value,
            'is_active' => true,
            'settings' => [],
        ]);
        $currentOwner->exists = true;

        $repository->shouldReceive('findTenantAdminByTenantChannelTypeAndJid')
            ->once()
            ->with(15, 'email', 'owner2@demo.test')
            ->andReturn(null);

        $repository->shouldReceive('findOwnerTenantAdminByTenantId')
            ->once()
            ->with(15)
            ->andReturn($currentOwner);

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('El tenant ya cuenta con un owner activo.');

        $useCase->execute($tenantAdminData);
    }

    public function test_it_rejects_downgrading_the_existing_owner_without_transfer_flow(): void
    {
        $repository = Mockery::mock(TenantAdminRepositoryInterface::class);
        $useCase = new RegisterTenantAdmin($repository);
        $tenantAdminData = new TenantAdminData(
            tenantId: 15,
            channelType: 'whatsapp',
            jid: '5217711986426@s.whatsapp.net',
            role: TenantAdminRole::Admin->value,
        );

        $currentOwner = new TenantAdmin([
            'id' => 10,
            'tenant_id' => 15,
            'channel_type' => 'whatsapp',
            'jid' => '5217711986426@s.whatsapp.net',
            'role' => TenantAdminRole::Owner->value,
            'is_active' => true,
            'settings' => [],
        ]);
        $currentOwner->exists = true;

        $repository->shouldReceive('findTenantAdminByTenantChannelTypeAndJid')
            ->once()
            ->with(15, 'whatsapp', '5217711986426@s.whatsapp.net')
            ->andReturn($currentOwner);

        $repository->shouldReceive('findOwnerTenantAdminByTenantId')
            ->once()
            ->with(15)
            ->andReturn($currentOwner);

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage(
            'No se puede remover el owner del tenant sin un flujo explícito de transferencia.'
        );

        $useCase->execute($tenantAdminData);
    }
}
