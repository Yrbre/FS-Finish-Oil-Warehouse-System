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
     * Bulan produksi 'YYYY-MM' → 'YYYY-MM-01' untuk SETIAP entry.
     * exp_date dihitung di service (produksi + 1 tahun).
     */
    protected function prepareForValidation(): void
    {
        $entries = $this->input('entries', []);

        foreach ($entries as $i => $entry) {
            if (! empty($entry['production_date'])) {
                $entries[$i]['production_date'] = $entry['production_date'] . '-01';
            }
        }

        $this->merge(['entries' => $entries]);
    }

    public function rules(): array
    {
        return [
            'entries'                   => ['required', 'array', 'min:1'],
            'entries.*.item_id'         => ['required', 'exists:items,id'],
            'entries.*.warehouse_id'    => ['required', 'exists:warehouses,id'],
            'entries.*.trans_date'      => ['required', 'date'],
            'entries.*.trans_qty'       => ['required', 'numeric', 'gt:0'],
            'entries.*.vendor_lot'      => ['required', 'string', 'max:100'],
            'entries.*.production_date' => ['required', 'date'],
            'entries.*.qty_unit'        => ['nullable', 'numeric', 'min:0'],
            'entries.*.package'         => ['nullable', 'string', 'max:50'],
            'entries.*.notes'           => ['nullable', 'string'],
            'entries.*.demander_id'     => ['required', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.required'                 => 'Minimal harus ada 1 form transaksi.',
            'entries.*.trans_qty.gt'           => 'Quantity harus lebih besar dari 0.',
            'entries.*.vendor_lot.required'    => 'Vendor lot wajib diisi.',
            'entries.*.production_date.required' => 'Bulan produksi wajib diisi.',
            'entries.*.item_id.required'       => 'Item wajib dipilih.',
            'entries.*.demander_id.required'   => 'Demander wajib dipilih.',
            'entries.*.warehouse_id.required'  => 'Gudang tujuan wajib dipilih.',
        ];
    }

    /**
     * Supaya pesan error menyebut "Form ke-berapa", bukan "entries.2.item_id".
     */
    public function attributes(): array
    {
        $attributes = [];

        foreach ((array) $this->input('entries', []) as $i => $entry) {
            $formNo = $i + 1;
            $attributes["entries.$i.item_id"]         = "Item (Form #$formNo)";
            $attributes["entries.$i.demander_id"]      = "Demander (Form #$formNo)";
            $attributes["entries.$i.warehouse_id"]     = "Gudang (Form #$formNo)";
            $attributes["entries.$i.trans_date"]       = "Tanggal Masuk (Form #$formNo)";
            $attributes["entries.$i.vendor_lot"]       = "Vendor Lot (Form #$formNo)";
            $attributes["entries.$i.production_date"]  = "Bulan Produksi (Form #$formNo)";
            $attributes["entries.$i.trans_qty"]        = "Berat/Qty (Form #$formNo)";
        }

        return $attributes;
    }
}
