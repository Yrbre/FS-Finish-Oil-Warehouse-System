<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RelocationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'item_location_id' => ['required', 'exists:item_locations,id'],
            'to_warehouse_id'  => ['required', 'exists:warehouses,id'],
            'package_moved'    => ['required', 'integer', 'gt:0'],
            'reason'           => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_location_id.required' => 'Lot yang dipindahkan wajib dipilih.',
            'to_warehouse_id.required'  => 'Gudang tujuan wajib dipilih.',
            'package_moved.required'    => 'Jumlah package wajib diisi.',
            'package_moved.integer'     => 'Jumlah package harus bilangan bulat.',
        ];
    }
}
