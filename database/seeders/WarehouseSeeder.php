<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class WarehouseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $warehouses = [
            ['tag' => 'WH001', 'name' => 'Warehouse 1', 'department_id' => \App\Models\Department::where('code', 'IMC')->first()->id],
            ['tag' => 'WH002', 'name' => 'Warehouse 2', 'department_id' => \App\Models\Department::where('code', 'IMC')->first()->id],
            ['tag' => 'WH003', 'name' => 'Warehouse 3', 'department_id' => \App\Models\Department::where('code', 'IMC')->first()->id],
        ];

        foreach ($warehouses as $warehouse) {
            \App\Models\Warehouse::firstOrCreate($warehouse);
        }
    }
}
