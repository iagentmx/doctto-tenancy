<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EspoCrmOpportunityUpdatedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $prefix = $this->isJsonArray() ? '*.' : '';

        return [
            $prefix . 'id' => ['required', 'string'],
            $prefix . 'stage' => ['required', 'string'],
        ];
    }

    private function isJsonArray(): bool
    {
        $data = $this->json()->all();
        return is_array($data) && array_is_list($data);
    }
}
