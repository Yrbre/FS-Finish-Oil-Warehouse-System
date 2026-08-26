<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReceiptOfGoodsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Boleh backdate — tanggal barang benar-benar dikirim.
            // Nomor suratnya tetap memakai urutan saat diterbitkan.
            'letter_date' => ['required', 'date'],
            'photo'       => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ];
    }

    public function messages(): array
    {
        return [
            'letter_date.required' => 'Tanggal surat wajib diisi.',
            'photo.image'          => 'File harus berupa gambar.',
            'photo.max'            => 'Ukuran foto maksimal 2 MB.',
        ];
    }
}
