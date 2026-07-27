<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemLocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Normalisasi format input sebelum validasi.
     * production_date dari input type=month berformat 'YYYY-MM',
     * diubah jadi tanggal 1 bulan itu: 'YYYY-MM-01'.
     *
     * Catatan: perhitungan exp_date (produksi + 1 tahun) BUKAN di sini —
     * itu business rule, ditangani di ItemLocationService::create()/update()
     * supaya konsisten dari pintu masuk manapun (form manual, PORC, dll).
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
            'vendor_lot'      => ['nullable', 'string', 'max:100'],
            'production_date' => ['nullable', 'date'],
            'qty_weight'      => ['required', 'numeric', 'min:0'],
            'qty_unit'        => ['nullable', 'numeric', 'min:0'],
            'package'         => ['nullable', 'string', 'max:50'],
            'type'            => ['nullable', 'string', 'max:50'],
            'received_date'   => ['nullable', 'date'],
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id'         => 'item',
            'warehouse_id'    => 'gudang',
            'qty_weight'      => 'berat (qty)',
            'production_date' => 'tanggal produksi',
        ];
    }
}
