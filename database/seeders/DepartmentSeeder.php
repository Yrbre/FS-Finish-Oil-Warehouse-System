<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = [
            ['code' => 'IMC', 'name' => 'Inventory Management Center'],
            ['code' => 'FY1', 'name' => 'Filament 1'],
            ['code' => 'FY2', 'name' => 'Filament 2'],
            ['code' => 'FY3', 'name' => 'Filament 3'],
            ['code' => 'SF', 'name' => 'Staple Fiber'],
            ['code' => 'PBX', 'name' => 'Polymer BX'],
            ['code' => 'PCP', 'name' => 'Polymer CP'],
        ];

        foreach ($departments as $department) {
            Department::firstOrCreate($department);
        }
    }
}
