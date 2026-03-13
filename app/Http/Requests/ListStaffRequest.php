<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasTenantStaffRouteValidation;
use Illuminate\Foundation\Http\FormRequest;

class ListStaffRequest extends FormRequest
{
    use HasTenantStaffRouteValidation;

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_jid' => $this->tenantJidRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTenantJidValidation();
    }
}
