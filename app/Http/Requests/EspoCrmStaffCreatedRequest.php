<?php

namespace App\Http\Requests;

use App\Enums\StaffRole;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EspoCrmStaffCreatedRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    private function isJsonArray(): bool
    {
        $data = $this->json()->all();
        return is_array($data) && array_is_list($data);
    }

    public function rules(): array
    {
        $p = $this->isJsonArray() ? '*.' : '';

        return [
            $p . 'id'         => ['required', 'string'],
            $p . 'name'       => ['required', 'string'],
            $p . 'role'       => ['required', 'string', Rule::in(StaffRole::values())],
            $p . 'phone'      => ['nullable', 'string'],
            $p . 'specialty'  => ['nullable', 'string'],
            $p . 'about'      => ['nullable', 'string'],
            $p . 'active'     => ['required', 'boolean'],
            $p . 'accountId'  => ['required', 'string'],

            // Horarios
            $p . 'mondayEnabled'    => ['required', 'boolean'],
            $p . 'mondayStart'      => ['nullable', 'string'],
            $p . 'mondayEnd'        => ['nullable', 'string'],

            $p . 'tuesdayEnabled'   => ['required', 'boolean'],
            $p . 'tuesdayStart'     => ['nullable', 'string'],
            $p . 'tuesdayEnd'       => ['nullable', 'string'],

            $p . 'wednesdayEnabled' => ['required', 'boolean'],
            $p . 'wednesdayStart'   => ['nullable', 'string'],
            $p . 'wednesdayEnd'     => ['nullable', 'string'],

            $p . 'thursdayEnabled'  => ['required', 'boolean'],
            $p . 'thursdayStart'    => ['nullable', 'string'],
            $p . 'thursdayEnd'      => ['nullable', 'string'],

            $p . 'fridayEnabled'    => ['required', 'boolean'],
            $p . 'fridayStart'      => ['nullable', 'string'],
            $p . 'fridayEnd'        => ['nullable', 'string'],

            $p . 'saturdayEnabled'  => ['required', 'boolean'],
            $p . 'saturdayStart'    => ['nullable', 'string'],
            $p . 'saturdayEnd'      => ['nullable', 'string'],

            $p . 'sundayEnabled'    => ['required', 'boolean'],
            $p . 'sundayStart'      => ['nullable', 'string'],
            $p . 'sundayEnd'        => ['nullable', 'string'],
        ];
    }

}
