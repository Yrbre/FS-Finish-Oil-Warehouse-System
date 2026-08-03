<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $roleId = $this->route('id');

        return [
            'name' => [
                'required',
                'string',
                'max:50',
                'alpha_dash',
                Rule::unique('roles', 'name')->ignore($roleId),
            ],
            'permissions'   => ['nullable', 'array'],
            'permissions.*' => ['exists:permissions,name'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nama role'];
    }

    public function messages(): array
    {
        return [
            'name.alpha_dash' => 'Nama role hanya boleh huruf, angka, strip, dan underscore (tanpa spasi).',
        ];
    }
}
