<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTenantCatalogRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'tenant_id' => ['required', 'integer', 'min:1'],
        ];
    }

    public function messages(): array
    {
        return [
            'tenant_id.required' => 'The tenant_id field is required.',
            'tenant_id.integer' => 'The tenant_id field must be an integer.',
            'tenant_id.min' => 'The tenant_id field must be at least 1.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'tenant_id' => $this->route('tenantId'),
        ]);
    }
}
