<?php

namespace Tests\Unit\Modules\TenantEntities\UseCases\Staff;

use App\Exceptions\ApiServiceException;
use App\Models\Staff;
use App\Modules\TenantEntities\DTO\StaffData;
use App\Modules\TenantEntities\UseCases\Staff\CreateStaff;
use App\Modules\TenantEntities\UseCases\Staff\DeleteStaff;
use App\Modules\TenantEntities\UseCases\Staff\GetStaff;
use App\Modules\TenantEntities\UseCases\Staff\ListStaff;
use App\Modules\TenantEntities\UseCases\Staff\UpdateStaff;
use App\Repositories\Contracts\StaffRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Mockery;
use Tests\TestCase;

class StaffCrudUseCasesTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_create_staff_delegates_to_repository(): void
    {
        $staffData = $this->makeStaffData();
        $expectedStaff = new Staff(['id' => 4, 'name' => 'Dra. Perez']);

        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('createStaff')
            ->once()
            ->with($staffData->toRepositoryData())
            ->andReturn($expectedStaff);

        $useCase = new CreateStaff($repository);

        $this->assertSame($expectedStaff, $useCase->execute($staffData));
    }

    public function test_list_staff_returns_repository_collection(): void
    {
        $staffCollection = new Collection([new Staff(['id' => 4]), new Staff(['id' => 5])]);

        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('listStaffByTenantId')
            ->once()
            ->with(20)
            ->andReturn($staffCollection);

        $useCase = new ListStaff($repository);

        $this->assertSame($staffCollection, $useCase->execute(20));
    }

    public function test_get_staff_throws_when_staff_does_not_belong_to_tenant(): void
    {
        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('findStaffByTenantAndId')
            ->once()
            ->with(20, 4)
            ->andReturn(null);

        $useCase = new GetStaff($repository);

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('Staff no encontrado');

        $useCase->execute(20, 4);
    }

    public function test_update_staff_updates_existing_staff_only_within_the_tenant_scope(): void
    {
        $staffData = $this->makeStaffData();
        $existingStaff = new Staff(['id' => 4, 'tenant_id' => 20, 'name' => 'Dra. Perez']);
        $existingStaff->id = 4;
        $updatedStaff = new Staff(['id' => 4, 'tenant_id' => 20, 'name' => 'Dra. Ana']);
        $updatedStaff->id = 4;

        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('findStaffByTenantAndId')
            ->once()
            ->with(20, 4)
            ->andReturn($existingStaff);
        $repository->shouldReceive('updateStaff')
            ->once()
            ->with(4, $staffData->toRepositoryData())
            ->andReturn($updatedStaff);

        $useCase = new UpdateStaff($repository);

        $this->assertSame($updatedStaff, $useCase->execute(20, 4, $staffData));
    }

    public function test_delete_staff_throws_when_staff_is_not_found_in_tenant(): void
    {
        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('findStaffByTenantAndId')
            ->once()
            ->with(20, 99)
            ->andReturn(null);

        $useCase = new DeleteStaff($repository);

        $this->expectException(ApiServiceException::class);
        $this->expectExceptionMessage('Staff no encontrado');

        $useCase->execute(20, 99);
    }

    public function test_delete_staff_calls_repository_when_staff_exists(): void
    {
        $existingStaff = new Staff(['id' => 7, 'tenant_id' => 20]);
        $existingStaff->id = 7;

        $repository = Mockery::mock(StaffRepositoryInterface::class);
        $repository->shouldReceive('findStaffByTenantAndId')
            ->once()
            ->with(20, 7)
            ->andReturn($existingStaff);
        $repository->shouldReceive('deleteStaffById')
            ->once()
            ->with(7);

        $useCase = new DeleteStaff($repository);

        $useCase->execute(20, 7);
        $this->addToAssertionCount(1);
    }

    private function makeStaffData(): StaffData
    {
        return new StaffData(
            tenantId: 20,
            name: 'Dra. Ana',
            role: 'doctor',
            phone: '7710000000',
            email: 'dra@test.mx',
            isActive: true,
            about: 'Perfil',
            specialty: 'Ortodoncia',
        );
    }
}
