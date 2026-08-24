<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PorcUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Bulan produksi 'YYYY-MM' → 'YYYY-MM-01'.
     */
    protected function prepareForValidation(): void
    {
        if ($this->filled('production_date') && strlen($this->input('production_date')) === 7) {
            $this->merge([
                'production_date' => $this->input('production_date') . '-01',
            ]);
        }
    }

    public function rules(): array
    {
        return [
            // Wajib — tanpa alasan tertulis, audit tidak tahu KENAPA
            // angkanya berubah. edited_at/edited_by hanya mencatat
            // siapa dan kapan.
            'edit_reason'     => ['required', 'string', 'min:5', 'max:500'],

            'vendor_lot'      => ['required', 'string', 'max:100'],
            'production_date' => ['required', 'date'],
            'package'         => ['required', 'string', 'max:50'],
            'notes'           => ['nullable', 'string'],

            // Hanya terkirim kalau lot masih utuh — input di-disable
            // oleh view saat lot sudah terpakai. Service tetap
            // memvalidasi ulang, jangan percaya form saja.
            'qty_perpackage'  => ['nullable', 'numeric', 'gt:0', 'decimal:0,4'],
            'qty_package'     => ['nullable', 'integer', 'gt:0'],
        ];
    }

    public function messages(): array
    {
        return [
            'edit_reason.required'  => 'Alasan perubahan wajib diisi.',
            'edit_reason.min'       => 'Alasan perubahan terlalu singkat, jelaskan lebih detail.',
            'qty_package.integer'   => 'Jumlah kemasan harus bilangan bulat — kemasan di IMC tidak boleh terbuka.',
            'qty_perpackage.gt'     => 'Ukuran per kemasan harus lebih besar dari 0.',
        ];
    }

    public function attributes(): array
    {
        return [
            'edit_reason'     => 'Alasan Perubahan',
            'vendor_lot'      => 'Vendor Lot',
            'production_date' => 'Bulan Produksi',
            'package'         => 'Jenis Kemasan',
            'qty_perpackage'  => 'Isi per Kemasan',
            'qty_package'     => 'Jumlah Kemasan',
        ];
    }
}
