<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTenantByEspoIdRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'espocrm_id' => ['required', 'string', 'max:64'],
        ];
    }

    public function messages(): array
    {
        return [
            'espocrm_id.required' => 'The espocrm_id field is required.',
            'espocrm_id.max'      => 'The espocrm_id may not be greater than 64 characters.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'espocrm_id' => $this->route('espocrmId'),
        ]);
    }
}
