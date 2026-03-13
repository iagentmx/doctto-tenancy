<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use App\Http\Requests\Concerns\HasTenantStaffRouteValidation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStaffRequest extends FormRequest
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
            'name' => ['required', 'string'],
            'role' => ['required', 'string', Rule::in(StaffRole::values())],
            'phone' => ['nullable', 'string'],
            'email' => ['nullable', 'email'],
            'is_active' => ['required', 'boolean'],
            'settings.about' => ['nullable', 'string'],
            'settings.specialty' => ['nullable', 'string'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->prepareTenantJidAndStaffIdValidation();
    }
}
