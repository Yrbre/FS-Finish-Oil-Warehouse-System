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

            // Ukuran kemasan dipilih dari daftar yang benar-benar
            // tersedia di IMC untuk department ini.
            'requested_perpackage'     => ['required', 'numeric', 'gt:0'],
            // Transfer memindahkan package UTUH, jadi harus bilangan bulat.
            'requested_package'        => ['required', 'integer', 'gt:0'],

            'destination_warehouse_id' => ['required', 'exists:warehouses,id'],
            'expected_date'            => ['required', 'date'],
            'notes'                    => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.required'                  => 'Item wajib dipilih.',
            'requested_perpackage.required'     => 'Ukuran kemasan wajib dipilih.',
            'requested_package.required'        => 'Jumlah package wajib diisi.',
            'requested_package.integer'         => 'Jumlah package harus bilangan bulat.',
            'requested_package.gt'              => 'Jumlah package harus lebih besar dari 0.',
            'destination_warehouse_id.required' => 'Gudang tujuan wajib dipilih.',
            'expected_date.required'            => 'Tanggal kebutuhan wajib diisi.',
        ];
    }

    public function attributes(): array
    {
        return [
            'item_id'                  => 'Item',
            'requested_perpackage'     => 'Ukuran Kemasan',
            'requested_package'        => 'Jumlah Package',
            'destination_warehouse_id' => 'Gudang Tujuan',
            'expected_date'            => 'Tanggal Kebutuhan',
        ];
    }
}
