<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            [
                'tag' => 'AS',
                'name' => 'FINISH OIL',
                'department_id' => \App\Models\Department::where('code', 'IMC')->first()->id,
            ],
            [
                'tag' => 'PTA',
                'name' => 'GUDANG',
                'department_id' => \App\Models\Department::where('code', 'IMC')->first()->id,
            ],
        ];

        $departmentId = \App\Models\Department::where('code', 'IMC')->first()->id;

        $i = 1;
        do {
            $warehouses[] = [
                'tag' => (string) $i,
                'name' => 'FINISH OIL',
                'department_id' => $departmentId,
            ];

            $i++;
        } while ($i <= 69);

        foreach ($warehouses as $warehouse) {
            \App\Models\Warehouse::firstOrCreate($warehouse);
        }
    }
}
