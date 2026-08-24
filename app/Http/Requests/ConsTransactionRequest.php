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
            'trans_date'   => ['required', 'date'],
            // CONS berbasis kg — FEFO otomatis memotong lot mana pun
            // milik department ini, lintas ukuran kemasan.
            'trans_qty'    => ['required', 'numeric', 'gt:0'],
            'notes'        => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required'      => 'Item wajib dipilih.',
            'warehouse_id.required' => 'Gudang wajib dipilih.',
            'trans_qty.required'    => 'Jumlah pemakaian wajib diisi.',
            'trans_qty.gt'          => 'Jumlah pemakaian harus lebih besar dari 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id'      => 'Item',
            'warehouse_id' => 'Gudang',
            'trans_date'   => 'Tanggal Pemakaian',
            'trans_qty'    => 'Jumlah Pemakaian',
        ];
    }
}
