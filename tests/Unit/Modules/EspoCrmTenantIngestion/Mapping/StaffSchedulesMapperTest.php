<?php

namespace Tests\Unit\Modules\EspoCrmTenantIngestion\Mapping;

use App\Enums\SchedulableType;
use App\Exceptions\EspoCrmWebhookException;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\StaffSchedulesMapper;
use Tests\TestCase;

class StaffSchedulesMapperTest extends TestCase
{
    public function test_it_maps_enabled_days_to_schedule_rows(): void
    {
        $rows = StaffSchedulesMapper::map(10, 20, 30, [
            'mondayEnabled' => true,
            'mondayStart' => '09:00:00',
            'mondayEnd' => '13:00:00',
            'wednesdayEnabled' => true,
            'wednesdayStart' => '16:00:00',
            'wednesdayEnd' => '18:00:00',
        ]);

        $this->assertCount(2, $rows);
        $this->assertSame(SchedulableType::Staff->value, $rows[0]['schedulable_type']);
        $this->assertSame(1, $rows[0]['day_of_week']);
        $this->assertSame(3, $rows[1]['day_of_week']);
    }

    public function test_it_requires_start_and_end_when_a_day_is_enabled(): void
    {
        $this->expectException(EspoCrmWebhookException::class);
        $this->expectExceptionMessage('Payload inválido: mondayEnabled=true requiere mondayStart y mondayEnd.');

        StaffSchedulesMapper::map(10, 20, 30, [
            'mondayEnabled' => true,
            'mondayStart' => '',
            'mondayEnd' => '13:00:00',
        ]);
    }
}
