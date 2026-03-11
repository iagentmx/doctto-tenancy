<?php

namespace Tests\Unit\Repositories;

use App\Enums\IntegrationEventDeliveryStatus;
use App\Repositories\IntegrationEventDeliveryRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Mockery;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;
use Tests\TestCase;

#[RunTestsInSeparateProcesses]
class IntegrationEventDeliveryRepositoryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        DB::clearResolvedInstances();

        parent::tearDown();
    }

    public function test_it_claims_only_pending_deliveries_that_are_ready_for_dispatch(): void
    {
        DB::shouldReceive('transaction')
            ->once()
            ->andReturnUsing(fn (callable $callback) => $callback());

        $query = Mockery::mock(Builder::class);
        $deliveryModel = Mockery::mock('alias:App\Models\IntegrationEventDelivery');
        $deliveryModel->shouldReceive('query')->once()->andReturn($query);

        $delivery = $deliveryModel;
        $delivery->id = 55;
        $delivery->status = IntegrationEventDeliveryStatus::Pending;
        $delivery->next_retry_at = now()->subMinute();
        $delivery->shouldReceive('save')->once();

        $query->shouldReceive('with')->once()->with('outbox')->andReturnSelf();
        $query->shouldReceive('lockForUpdate')->once()->andReturnSelf();
        $query->shouldReceive('find')->once()->with(55)->andReturn($delivery);

        $claimed = (new IntegrationEventDeliveryRepository())->claimPendingIntegrationEventDelivery(55);

        $this->assertSame(IntegrationEventDeliveryStatus::Processing, $claimed?->status);
    }

    public function test_it_marks_deliveries_as_delivered_and_clears_retry_state(): void
    {
        $query = Mockery::mock(Builder::class);
        $deliveryModel = Mockery::mock('alias:App\Models\IntegrationEventDelivery');
        $deliveryModel->shouldReceive('query')->once()->andReturn($query);

        $delivery = new FakeDelivery();

        $query->shouldReceive('find')->once()->with(55)->andReturn($delivery);

        (new IntegrationEventDeliveryRepository())->markIntegrationEventDeliveryAsDelivered(55, [
            'attempts' => 1,
            'response_status_code' => 202,
            'response_body' => 'ok',
        ]);

        $this->assertSame(IntegrationEventDeliveryStatus::Delivered, $delivery->filled['status']);
        $this->assertNull($delivery->filled['next_retry_at']);
        $this->assertNull($delivery->filled['last_error']);
        $this->assertSame(202, $delivery->filled['response_status_code']);
        $this->assertSame('ok', $delivery->filled['response_body']);
        $this->assertNotNull($delivery->filled['delivered_at']);
    }

    public function test_it_lists_pending_deliveries_filtered_by_destination_and_retry_window(): void
    {
        $query = Mockery::mock(Builder::class);
        $deliveryModel = Mockery::mock('alias:App\Models\IntegrationEventDelivery');
        $deliveryModel->shouldReceive('query')->once()->andReturn($query);

        $result = new Collection([(object) ['id' => 1]]);

        $query->shouldReceive('where')->once()->with('status', IntegrationEventDeliveryStatus::Pending->value)->andReturnSelf();
        $query->shouldReceive('when')
            ->once()
            ->with('n8n', Mockery::type('callable'))
            ->andReturnUsing(function ($destination, callable $callback) use ($query) {
                $callback($query);

                return $query;
            });
        $query->shouldReceive('where')->once()->with('destination', 'n8n')->andReturnSelf();
        $query->shouldReceive('where')->once()->with(Mockery::type('callable'))->andReturnUsing(function (callable $callback) use ($query) {
            $nested = Mockery::mock();
            $nested->shouldReceive('whereNull')->once()->with('next_retry_at')->andReturnSelf();
            $nested->shouldReceive('orWhere')->once()->with('next_retry_at', '<=', Mockery::type(\Illuminate\Support\Carbon::class))->andReturnSelf();
            $callback($nested);

            return $query;
        });
        $query->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $query->shouldReceive('limit')->once()->with(10)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($result);

        $deliveries = (new IntegrationEventDeliveryRepository())->listPendingIntegrationEventDeliveriesReadyForDispatch('n8n', 10);

        $this->assertSame($result, $deliveries);
    }

    public function test_it_requeues_failed_deliveries_and_clears_the_next_retry_date(): void
    {
        $query = Mockery::mock(Builder::class);
        $deliveryModel = Mockery::mock('alias:App\Models\IntegrationEventDelivery');
        $deliveryModel->shouldReceive('query')->once()->andReturn($query);

        $first = new FakeDelivery();
        $second = new FakeDelivery();

        $collection = new Collection([$first, $second]);

        $query->shouldReceive('where')->once()->with('status', IntegrationEventDeliveryStatus::Failed->value)->andReturnSelf();
        $query->shouldReceive('when')
            ->once()
            ->with('n8n', Mockery::type('callable'))
            ->andReturnUsing(function ($destination, callable $callback) use ($query) {
                $callback($query);

                return $query;
            });
        $query->shouldReceive('where')->once()->with('destination', 'n8n')->andReturnSelf();
        $query->shouldReceive('orderBy')->once()->with('id')->andReturnSelf();
        $query->shouldReceive('limit')->once()->with(2)->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn($collection);

        $count = (new IntegrationEventDeliveryRepository())->requeueFailedIntegrationEventDeliveries('n8n', 2);

        $this->assertSame(2, $count);
        $this->assertSame(IntegrationEventDeliveryStatus::Pending, $first->status);
        $this->assertNull($first->next_retry_at);
        $this->assertSame(IntegrationEventDeliveryStatus::Pending, $second->status);
        $this->assertNull($second->next_retry_at);
    }
}

class FakeDelivery
{
    public array $filled = [];
    public mixed $status = null;
    public mixed $next_retry_at = null;
    public mixed $id = null;

    public function fill(array $attributes)
    {
        $this->filled = $attributes;

        foreach ($attributes as $key => $value) {
            $this->{$key} = $value;
        }

        return $this;
    }

    public function save(array $options = []): bool
    {
        return true;
    }
}
