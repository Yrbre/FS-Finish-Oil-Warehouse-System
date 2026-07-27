<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class DepartmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Saat update, abaikan record yang sedang diedit dari cek unique
        $departmentId = $this->route('department');

        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('departments', 'code')->ignore($departmentId),
            ],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nama department',
            'code' => 'kode department',
        ];
    }
}
