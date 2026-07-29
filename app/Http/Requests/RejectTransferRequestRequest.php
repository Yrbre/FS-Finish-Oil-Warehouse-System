<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectTransferRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reject_reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'reject_reason.required' => 'Alasan penolakan wajib diisi.',
        ];
    }
}
