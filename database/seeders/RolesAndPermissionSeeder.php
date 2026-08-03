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

            // Item Locations (Stok Gudang)
            'item-locations.view',
            'item-locations.create',
            'item-locations.update',
            'item-locations.delete',

            // Transaksi — dipecah per jenis. "view" mengontrol jenis apa
            // saja yang tampil di daftar transaksi untuk role tersebut.
            // Delete hanya ada untuk PORC (CONS/ADJ tidak bisa dihapus,
            // dikoreksi lewat ADJ baru — aturan ini di service, bukan permission).
            'transactions.porc.view',
            'transactions.porc.create',
            'transactions.porc.delete',
            'transactions.cons.view',
            'transactions.cons.create',
            'transactions.adj.view',
            'transactions.adj.create',

            // Transfer Request — aksinya sendiri-sendiri karena masing-masing
            // punya aturan otorisasi berbeda (approve = IMC, receive = penerima).
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

        // imc — pusat gudang: kelola stok, SEMUA jenis transaksi
        // (termasuk hapus PORC), approve/reject/receive transfer,
        // lihat laporan & item master (read-only)
        $imc = Role::firstOrCreate(['name' => 'imc']);
        $imc->syncPermissions([
            'item-locations.view',
            'item-locations.create',
            'item-locations.update',
            'item-locations.delete',
            'transactions.porc.view',
            'transactions.porc.create',
            'transactions.porc.delete',
            'transactions.cons.view',
            'transactions.cons.create',
            'transactions.adj.view',
            'transactions.adj.create',
            'transfer-requests.view',
            'transfer-requests.approve',
            'transfer-requests.reject',
            'transfer-requests.receive',
            'items.view',
            'reports.view',
        ]);

        // manager — pemantau: lihat laporan & item master saja
        $manager = Role::firstOrCreate(['name' => 'manager']);
        $manager->syncPermissions([
            'reports.view',
            'items.view',
        ]);

        // staff — operasional harian: HANYA CONS (pemakaian). PORC (terima
        // dari vendor) dan ADJ (koreksi) sengaja tidak diberikan — itu
        // wewenang IMC/admin.
        $staff = Role::firstOrCreate(['name' => 'staff']);
        $staff->syncPermissions([
            'items.view',
            'transactions.cons.view',
            'transactions.cons.create',
            'transfer-requests.view',
            'transfer-requests.create',
            'transfer-requests.cancel',
            'transfer-requests.receive',
        ]);
    }
}
