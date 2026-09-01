<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MinimumStockRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'item_id'       => [
                'required',
                'exists:items,id',
                // Satu item hanya boleh punya satu ambang per department.
                Rule::unique('minimum_stocks')
                    ->where(fn($q) => $q->where('department_id', $this->department_id))
                    ->ignore($id),
            ],
            'department_id' => ['required', 'exists:departments,id'],
            'min_stock'     => ['required', 'numeric', 'min:0'],
            'is_active'     => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'item_id.unique'    => 'Item ini sudah punya pengaturan minimum stock untuk department tersebut.',
            'min_stock.required' => 'Ambang minimum wajib diisi.',
        ];
    }
}
