<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $itemId = $this->route('item');

        return [
            'item_no' => [
                'required',
                'string',
                'max:100',
                Rule::unique('items', 'item_no')->ignore($itemId),
            ],
            'item_desc' => ['required', 'string', 'max:255'],
            'item_uom'  => ['required', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return [
            'item_no'   => 'kode item',
            'item_desc' => 'nama item',
            'item_uom'  => 'satuan',
        ];
    }
}
