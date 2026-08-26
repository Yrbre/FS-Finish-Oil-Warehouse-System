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


    protected function prepareForValidation(): void
    {
        if ($this->input('password') === '') {
            $this->merge(['password' => null]);
        }
    }

    public function rules(): array
    {
        $userId = $this->route('id');
        $isEdit = (bool) $userId;

        return [
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($userId),
            ],
            'password'              => [$isEdit ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'department_id'         => ['required', 'exists:departments,id'],
            'role'                  => ['required', 'exists:roles,name'],
            'is_transfer_approver'  => ['nullable', 'boolean'],
            'can_issue_receipt'     => ['nullable', 'boolean'],
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
