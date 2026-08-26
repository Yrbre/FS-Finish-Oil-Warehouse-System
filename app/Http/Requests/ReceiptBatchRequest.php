<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'ids'         => ['required', 'array', 'min:1'],
            'ids.*'       => ['exists:transfer_requests,id'],
            // Tanggal barang dikirim. Boleh backdate — nomor suratnya
            // tetap mengikuti urutan saat diterbitkan.
            'letter_date' => ['required', 'date'],
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required'         => 'Pilih minimal 1 permintaan untuk dicetak.',
            'letter_date.required' => 'Tanggal kirim wajib diisi.',
        ];
    }
}
