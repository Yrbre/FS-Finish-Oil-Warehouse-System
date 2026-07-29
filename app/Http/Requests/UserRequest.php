<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $userId  = $this->route('user');
        $isEdit  = (bool) $userId;

        return [
            'name'          => ['required', 'string', 'max:255'],
            'email'         => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            // Wajib saat create, opsional saat edit (kosong = tidak diubah)
            'password'      => [$isEdit ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'department_id' => ['required', 'exists:departments,id'],
            'role'          => ['required', 'exists:roles,name'],
            'is_transfer_approver' => ['nullable', 'boolean'],
        ];
    }

    public function attributes(): array
    {
        return [
            'department_id' => 'department',
            'role'          => 'role',
        ];
    }
}
