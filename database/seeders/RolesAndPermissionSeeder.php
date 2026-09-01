<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Permission dipecah granular per aksi (view/create/update/delete),
        // bukan 1 permission gabungan seperti sebelumnya. Supaya di form
        // Role bisa dicentang satu-satu.
        $permissions = [
            // Department
            'departments.view',
            'departments.create',
            'departments.update',
            'departments.delete',

            // Warehouse
            'warehouses.view',
            'warehouses.create',
            'warehouses.update',
            'warehouses.delete',

            // Item Master
            'items.view',
            'items.create',
            'items.update',
            'items.delete',

            // Minimum Stock
            'minimum-stocks.view',
            'minimum-stocks.create',
            'minimum-stocks.update',
            'minimum-stocks.delete',

            // Item Locations (Stok Gudang)
            // 'item-locations.create' dipertahankan untuk kompatibilitas,
            // tapi routenya dinonaktifkan — stok awal kini lewat PORC.
            'item-locations.view',
            'item-locations.create',
            'item-locations.update',
            'item-locations.delete',
            'item-locations.dispose',

            // Item Locations (Stok Gudang)
            // Kebutuhan Untuk Relocation Stock Gudang
            'relocations.view',
            'relocations.create',

            // Transaksi
            // PORC hanya di gudang IMC. CONS & ADJ hanya di gudang
            // department — aturan zona ini ditegakkan guardZone()
            // di TransactionService, bukan lewat permission.
            'transactions.porc.view',
            'transactions.porc.create',
            'transactions.porc.update',   // ← BARU: edit koreksi salah input
            'transactions.porc.delete',
            'transactions.cons.view',
            'transactions.cons.create',
            'transactions.adj.view',
            'transactions.adj.create',

            // Transfer Request
            'transfer-requests.view',
            'transfer-requests.create',
            'transfer-requests.cancel',
            'transfer-requests.approve',
            'transfer-requests.reject',
            'transfer-requests.receive',

            // User Management
            'users.view',
            'users.create',
            'users.update',
            'users.delete',

            // Role Management
            'roles.view',
            'roles.create',
            'roles.update',
            'roles.delete',

            // Laporan
            'reports.view',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // admin — akses penuh
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $admin->syncPermissions($permissions);

        // imc — pengelola gudang pusat.
        // CONS dan ADJ DICABUT: keduanya hanya sah di gudang department,
        // dan guardZone() akan selalu menolaknya di gudang IMC.
        $imc = Role::firstOrCreate(['name' => 'imc']);
        $imc->syncPermissions([
            'item-locations.view',
            'item-locations.update',
            'item-locations.delete',
            'transactions.porc.view',
            'transactions.porc.create',
            'transactions.porc.update',
            'transactions.porc.delete',
            // Tetap bisa MELIHAT transaksi department untuk pemantauan,
            // tapi tidak bisa membuatnya.
            'transactions.cons.view',
            'transactions.adj.view',
            'transfer-requests.view',
            'transfer-requests.approve',
            'transfer-requests.reject',
            'items.view',
            'reports.view',
        ]);

        // manager — pemantau
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'reports.view',
            'items.view',
        ]);

        // staff — operasional di gudang department.
        // ADJ DITAMBAHKAN: koreksi stok hanya sah di gudang department,
        // dan staff yang tahu kondisi fisiknya.
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'items.view',
            'transactions.cons.view',
            'transactions.cons.create',
            'transactions.adj.view',
            'transactions.adj.create',
            'transfer-requests.view',
            'transfer-requests.create',
            'transfer-requests.cancel',
            'transfer-requests.receive',
        ]);
    }
}
