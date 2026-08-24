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
            'entries.*.demander_id'     => ['required', 'exists:departments,id'],
            'entries.*.trans_date'      => ['required', 'date'],

            // Berat TIDAK diinput — dihitung dari dua kolom di bawah.
            // qty_perpackage 4 desimal supaya kemasan seperti 18,3333 kg
            // tidak kehilangan presisi saat dikalikan.
            'entries.*.qty_perpackage'  => ['required', 'numeric', 'gt:0', 'decimal:0,4'],
            // Package di IMC selalu utuh, jadi harus bilangan bulat.
            'entries.*.qty_package'     => ['required', 'integer', 'gt:0'],

            'entries.*.vendor_lot'      => ['required', 'string', 'max:100'],
            'entries.*.production_date' => ['required', 'date'],
            'entries.*.package'         => ['required', 'string', 'max:50'],
            'entries.*.notes'           => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'entries.required'                   => 'Minimal harus ada 1 form transaksi.',
            'entries.*.item_id.required'         => 'Item wajib dipilih.',
            'entries.*.demander_id.required'     => 'Demander wajib dipilih.',
            'entries.*.warehouse_id.required'    => 'Gudang tujuan wajib dipilih.',
            'entries.*.qty_perpackage.required'  => 'Ukuran per kemasan wajib diisi.',
            'entries.*.qty_perpackage.gt'        => 'Ukuran per kemasan harus lebih besar dari 0.',
            'entries.*.qty_package.required'     => 'Jumlah kemasan wajib diisi.',
            'entries.*.qty_package.integer'      => 'Jumlah kemasan harus bilangan bulat — kemasan di IMC tidak boleh terbuka.',
            'entries.*.qty_package.gt'           => 'Jumlah kemasan harus lebih besar dari 0.',
            'entries.*.vendor_lot.required'      => 'Vendor lot wajib diisi.',
            'entries.*.production_date.required' => 'Bulan produksi wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [];

        foreach ((array) $this->input('entries', []) as $i => $entry) {
            $formNo = $i + 1;
            $attributes["entries.$i.item_id"]        = "Item (Form #$formNo)";
            $attributes["entries.$i.demander_id"]    = "Demander (Form #$formNo)";
            $attributes["entries.$i.warehouse_id"]   = "Gudang (Form #$formNo)";
            $attributes["entries.$i.trans_date"]     = "Tanggal Masuk (Form #$formNo)";
            $attributes["entries.$i.vendor_lot"]     = "Vendor Lot (Form #$formNo)";
            $attributes["entries.$i.production_date"] = "Bulan Produksi (Form #$formNo)";
            $attributes["entries.$i.qty_perpackage"] = "Ukuran per Kemasan (Form #$formNo)";
            $attributes["entries.$i.qty_package"]    = "Jumlah Kemasan (Form #$formNo)";
            $attributes["entries.$i.package"]        = "Jenis Kemasan (Form #$formNo)";
        }

        return $attributes;
    }
}
