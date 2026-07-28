<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ConsTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'      => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'trans_date'   => ['required', 'date', 'before_or_equal:today'],
            'trans_qty'    => ['required', 'numeric', 'gt:0'],
            'notes'        => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'trans_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'trans_qty.gt'               => 'Quantity harus lebih besar dari 0.',
        ];
    }
}
