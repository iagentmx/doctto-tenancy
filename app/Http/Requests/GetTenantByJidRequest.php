<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GetTenantByJidRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'jid' => [
                'required',
                'string',
                'regex:/^521\d{10}@s\.whatsapp\.net$/'
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'jid.required' => 'The JID field is required.',
            'jid.regex'    => 'The JID must match the format 521XXXXXXXXXX@s.whatsapp.net (12 digits after 521).',
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'jid' => $this->route('tenantJid'),
        ]);
    }
}
