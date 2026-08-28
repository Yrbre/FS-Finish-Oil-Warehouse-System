<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DisposalRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Wajib — pembuangan barang harus bisa dipertanggungjawabkan.
            'disposal_reason' => ['required', 'string', 'min:10', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'disposal_reason.required' => 'Alasan pembuangan wajib diisi.',
            'disposal_reason.min'      => 'Alasan terlalu singkat, jelaskan kondisi barangnya.',
        ];
    }
}
