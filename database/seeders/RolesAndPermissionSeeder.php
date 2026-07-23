<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
            // Master data
            'manage-departments',
            'manage-warehouses',
            'manage-items',
            'manage-item-locations',

            // Transaksi
            'create-transaction',

            // Transfer
            'manage-transfer-request', // create & cancel request sendiri
            'approve-transfer',        // approver IMC
            'receive-transfer',        // konfirmasi terima di lapangan

            // User & Role management
            'manage-users',
            'manage-roles',

            // Reporting
            'view-reports',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Role: admin — semua akses
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // Role: imc — approver transfer + lihat laporan
        $imc = Role::firstOrCreate(['name' => 'imc']);
        $imc->syncPermissions([
            'approve-transfer',
            'view-reports',
            'manage-item-locations',
        ]);

        // Role: staff — operasional harian
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'create-transaction',
            'manage-transfer-request',
            'receive-transfer',
        ]);

        // Role: manager — lihat laporan saja
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'view-reports',
        ]);
    }
}
