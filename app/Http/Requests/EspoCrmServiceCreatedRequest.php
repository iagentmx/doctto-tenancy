<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EspoCrmServiceCreatedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Detecta si el payload enviado por EspoCRM es un array
     */
    private function isJsonArray(): bool
    {
        $data = $this->json()->all();
        return is_array($data) && array_is_list($data);
    }

    public function rules(): array
    {
        // Si viene como [ { ... } ] entonces usamos *.
        $p = $this->isJsonArray() ? '*.' : '';

        return [
            $p . 'id'          => ['required', 'string'],
            $p . 'name'        => ['required', 'string'],
            $p . 'description' => ['nullable', 'string'],
            $p . 'duration'    => ['required', 'integer'],
            $p . 'price'       => ['required'],
            $p . 'isActive'    => ['required', 'boolean'],
            $p . 'category'    => ['required', 'string'],
            $p . 'accountId'   => ['required', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            '*.id.required'          => 'El campo id es obligatorio.',
            '*.name.required'        => 'El nombre del servicio es obligatorio.',
            '*.duration.required'    => 'La duración es obligatoria.',
            '*.price.required'       => 'El precio es obligatorio.',
            '*.category.required'    => 'La categoría es obligatoria.',
            '*.accountId.required'   => 'El accountId es obligatorio.',
            '*.isActive.required'    => 'El campo isActive es obligatorio.',

            // Para cuando viene como objeto { ... }
            'id.required'            => 'El campo id es obligatorio.',
            'name.required'          => 'El nombre del servicio es obligatorio.',
            'duration.required'      => 'La duración es obligatoria.',
            'price.required'         => 'El precio es obligatorio.',
            'category.required'      => 'La categoría es obligatoria.',
            'accountId.required'     => 'El accountId es obligatorio.',
            'isActive.required'      => 'El campo isActive es obligatorio.',
        ];
    }
}
