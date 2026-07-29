<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        $this->call(RolesAndPermissionSeeder::class);
        $this->call(DepartmentSeeder::class);
        $this->call(WarehouseSeeder::class);
        $this->call(ItemSeeder::class);

        // Department awal — IMC sebagai pusat gudang (Gudang Finish Oil)
        $imcDepartment = Department::firstOrCreate(
            ['code' => 'IMC'],
            ['name' => 'Inventory Material Control']
        );

        // Akun admin awal
        $admin = User::firstOrCreate(
            ['email' => 'admin@tifico.co.id'],
            [
                'name'          => 'admin',
                'password'      => Hash::make('1'),
                'department_id' => $imcDepartment->id,
            ]
        );

        // Aturan bisnis: 1 user = 1 role
        $admin->assignSingleRole('admin');
    }
}
