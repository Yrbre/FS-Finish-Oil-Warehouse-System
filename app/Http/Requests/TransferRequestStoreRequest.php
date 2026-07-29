<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferRequestStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'                  => ['required', 'exists:items,id'],
            'requested_qty'            => ['required', 'numeric', 'gt:0'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id'],
            'expected_date'            => ['required', 'date', 'after_or_equal:today'],
            'notes'                    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'requested_qty.gt'            => 'Jumlah harus lebih besar dari 0.',
            'expected_date.after_or_equal' => 'Tanggal barang harus sampai tidak boleh sebelum hari ini.',
        ];
    }
}
