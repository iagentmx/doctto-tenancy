<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasTenantStaffRouteValidation;
use Illuminate\Foundation\Http\FormRequest;

class DeleteStaffRequest extends FormRequest
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
            'staff_id' => $this->staffIdRules(),
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTenantJidAndStaffIdValidation();
    }
}
