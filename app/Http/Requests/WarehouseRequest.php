<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('warehouses', 'code')->ignore($warehouseId),
            ],
            'department_id' => ['required', 'exists:departments,id'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name'          => 'nama gudang',
            'code'          => 'kode gudang',
            'department_id' => 'department',
        ];
    }
}
