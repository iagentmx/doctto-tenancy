<?php

namespace Tests\Unit;

use App\Repositories\StaffServiceRepository;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class StaffServiceRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_replaces_staff_services_without_relying_on_an_id_column(): void
    {
        $deleteBuilder = Mockery::mock(Builder::class);
        $insertBuilder = Mockery::mock(Builder::class);

        $repository = new StaffServiceRepository();

        DB::shouldReceive('table')
            ->once()
            ->with('staff_services')
            ->andReturn($deleteBuilder);

        $deleteBuilder->shouldReceive('where')
            ->once()
            ->with('staff_id', 10)
            ->andReturnSelf();

        $deleteBuilder->shouldReceive('delete')
            ->once();

        DB::shouldReceive('table')
            ->once()
            ->with('staff_services')
            ->andReturn($insertBuilder);

        $insertBuilder->shouldReceive('insert')
            ->once()
            ->with([
                ['staff_id' => 10, 'service_id' => 2],
                ['staff_id' => 10, 'service_id' => 3],
            ]);

        $repository->syncServices(10, ['2', 3, 3, 'invalid']);

        $this->addToAssertionCount(1);
    }

    public function test_it_deletes_existing_staff_services_and_skips_insert_when_the_list_is_empty(): void
    {
        $deleteBuilder = Mockery::mock(Builder::class);

        $repository = new StaffServiceRepository();

        DB::shouldReceive('table')
            ->once()
            ->with('staff_services')
            ->andReturn($deleteBuilder);

        $deleteBuilder->shouldReceive('where')
            ->once()
            ->with('staff_id', 15)
            ->andReturnSelf();

        $deleteBuilder->shouldReceive('delete')
            ->once();

        $repository->syncServices(15, []);

        $this->addToAssertionCount(1);
    }
}
