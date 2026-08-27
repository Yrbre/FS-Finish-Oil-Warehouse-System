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
            'destination_warehouse_id' => ['required', 'exists:warehouses,id'],
            'expected_date'            => ['required', 'date'],
            'notes'                    => ['nullable', 'string'],

            // Maksimal 10 item — batas ini mengikuti kapasitas
            // tabel di dokumen tanda terima.
            'items'                          => ['required', 'array', 'min:1', 'max:10'],
            'items.*.item_id'                => ['required', 'exists:items,id'],
            'items.*.requested_perpackage'   => ['required', 'numeric', 'gt:0'],
            // Transfer memindahkan package UTUH.
            'items.*.requested_package'      => ['required', 'integer', 'gt:0'],
        ];
    }

    /**
     * Item yang sama boleh diminta dua kali ASAL ukurannya berbeda.
     * Kombinasi item + ukuran yang sama harus digabung jadi satu baris.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $seen = [];

            foreach ((array) $this->input('items', []) as $i => $row) {
                $key = ($row['item_id'] ?? '') . '|' . ($row['requested_perpackage'] ?? '');

                if (isset($seen[$key])) {
                    $validator->errors()->add(
                        "items.$i.item_id",
                        "Item dan ukuran ini sudah ada di baris ke-" . ($seen[$key] + 1) .
                            ". Gabungkan jumlahnya jadi satu baris."
                    );
                }

                $seen[$key] = $i;
            }
        });
    }

    public function messages(): array
    {
        return [
            'items.required'                        => 'Minimal harus ada 1 item.',
            'items.max'                             => 'Maksimal 10 item per permintaan.',
            'items.*.item_id.required'              => 'Item wajib dipilih.',
            'items.*.requested_perpackage.required' => 'Ukuran kemasan wajib dipilih.',
            'items.*.requested_package.required'    => 'Jumlah package wajib diisi.',
            'items.*.requested_package.integer'     => 'Jumlah package harus bilangan bulat.',
            'destination_warehouse_id.required'     => 'Gudang tujuan wajib dipilih.',
            'expected_date.required'                => 'Tanggal kebutuhan wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        $attributes = [
            'destination_warehouse_id' => 'Gudang Tujuan',
            'expected_date'            => 'Tanggal Kebutuhan',
        ];

        foreach ((array) $this->input('items', []) as $i => $row) {
            $no = $i + 1;
            $attributes["items.$i.item_id"]              = "Item (baris #$no)";
            $attributes["items.$i.requested_perpackage"] = "Ukuran Kemasan (baris #$no)";
            $attributes["items.$i.requested_package"]    = "Jumlah Package (baris #$no)";
        }

        return $attributes;
    }
}
