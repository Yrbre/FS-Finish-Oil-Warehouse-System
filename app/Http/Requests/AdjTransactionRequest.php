<?php

namespace App\Http\Requests;

use App\Models\Transaction;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AdjTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_id'          => ['required', 'exists:items,id'],
            'warehouse_id'     => ['required', 'exists:warehouses,id'],
            'item_location_id' => ['required', 'exists:item_locations,id'],
            'adj_type'         => ['required', Rule::in([Transaction::ADJ_IN, Transaction::ADJ_OUT])],
            'trans_date'       => ['required', 'date', 'before_or_equal:today'],
            'trans_qty'        => ['required', 'numeric', 'gt:0'],
            'notes'            => ['required', 'string'], // adjustment wajib ada alasan
        ];
    }

    public function messages(): array
    {
        return [
            'trans_date.before_or_equal' => 'Tanggal transaksi tidak boleh melebihi hari ini.',
            'trans_qty.gt'               => 'Quantity harus lebih besar dari 0.',
            'item_location_id.required'  => 'Lot yang dikoreksi wajib dipilih.',
            'adj_type.required'          => 'Arah adjustment (tambah/kurang) wajib dipilih.',
            'notes.required'             => 'Alasan adjustment wajib diisi.',
        ];
    }
}
