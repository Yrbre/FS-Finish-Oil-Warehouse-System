<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse');

        return [
            'name' => ['required', 'string', 'max:255'],
            'tag' => [
                'required',
                'string',
                'max:50',
            ],
            'department_id' => ['required', 'exists:departments,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'nama gudang',
            'tag'           => 'kode gudang',
            'department_id' => 'department',
        ];
    }
}
