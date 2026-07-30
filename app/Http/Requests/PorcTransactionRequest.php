<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PorcTransactionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bulan produksi 'YYYY-MM' → 'YYYY-MM-01'.
     * exp_date dihitung di service (produksi + 1 tahun).
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('production_date')) {
            $this->merge([
                'production_date' => $this->production_date . '-01',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            'item_id'         => ['required', 'exists:items,id'],
            'warehouse_id'    => ['required', 'exists:warehouses,id'],
            'trans_date'      => ['required', 'date'],
            'trans_qty'       => ['required', 'numeric', 'gt:0'],
            'vendor_lot'      => ['required', 'string', 'max:100'],
            'production_date' => ['required', 'date'],
            'qty_unit'        => ['nullable', 'numeric', 'min:0'],
            'package'         => ['nullable', 'string', 'max:50'],
            'notes'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'trans_qty.gt'               => 'Quantity harus lebih besar dari 0.',
            'vendor_lot.required'        => 'Vendor lot wajib diisi.',
            'production_date.required'   => 'Bulan produksi wajib diisi.',
        ];
    }
}
