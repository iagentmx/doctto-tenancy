<?php

namespace Tests\Unit\Modules\EspoCrmTenantIngestion\Mapping;

use App\Enums\OperationType;
use App\Modules\EspoCrmTenantIngestion\Infrastructure\Mapping\AccountToTenantMapper;
use Tests\TestCase;

class AccountToTenantMapperTest extends TestCase
{
    public function test_it_builds_the_address_and_uses_the_declared_operation_type(): void
    {
        $result = AccountToTenantMapper::map([
            'id' => 'acc-1',
            'name' => 'Tenant Uno',
            'billingAddressStreet' => 'Av. Juarez 1',
            'billingAddressCity' => 'Pachuca',
            'billingAddressState' => 'Hidalgo',
            'billingAddressCountry' => 'Mexico',
            'billingAddressPostalCode' => '42000',
            'cTimeZone' => ' America/Mexico_City ',
            'operationType' => OperationType::MultiStaff->value,
            'cAssistantName' => 'Laura',
            'cReviewEnabled' => 1,
        ]);

        $this->assertSame(['espocrm_id' => 'acc-1'], $result['uniqueKeys']);
        $this->assertSame('Av. Juarez 1. Pachuca, Hidalgo. Mexico C.P. 42000', $result['tenantLocationData']['address']);
        $this->assertSame('America/Mexico_City', $result['tenantLocationData']['time_zone']);
        $this->assertSame(OperationType::MultiStaff->value, $result['tenantData']['operation_type']);
        $this->assertSame('Laura', $result['tenantData']['settings']['assistantName']);
        $this->assertTrue($result['tenantData']['settings']['features']['surveysEnabled']);
    }

    public function test_it_falls_back_to_defaults_when_optional_values_are_missing_or_invalid(): void
    {
        $result = AccountToTenantMapper::map([
            'id' => 'acc-2',
            'operationType' => 'invalid',
        ]);

        $this->assertNull($result['tenantLocationData']['address']);
        $this->assertNull($result['tenantLocationData']['time_zone']);
        $this->assertSame('Sin nombre', $result['tenantData']['name']);
        $this->assertSame('Sofia', iconv('UTF-8', 'ASCII//TRANSLIT', $result['tenantData']['settings']['assistantName']));
        $this->assertSame(OperationType::SingleStaff->value, $result['tenantData']['operation_type']);
        $this->assertFalse($result['tenantData']['settings']['features']['surveysEnabled']);
        $this->assertFalse($result['tenantData']['settings']['features']['billingEnabled']);
    }
}
