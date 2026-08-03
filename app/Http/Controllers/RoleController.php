<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class RoleController extends Controller
{
    /**
     * Role inti yang tidak boleh dihapus supaya sistem tidak "terkunci"
     * (misal tidak ada lagi yang bisa akses menu User Management).
     */
    private const PROTECTED_ROLES = ['admin'];

    public function index(\Illuminate\Http\Request $request)
    {
        try {
            if ($request->ajax()) {
                $roles = Role::withCount(['permissions', 'users']);

                return DataTables::of($roles)
                    ->addIndexColumn()
                    ->addColumn('name_upper', fn($row) => strtoupper($row->name))
                    ->addColumn('permissions_count', fn($row) => $row->permissions_count . ' permission')
                    ->addColumn('users_count', fn($row) => $row->users_count . ' user')
                    ->addColumn('action', function ($row) {
                        $btns = '';

                        if (auth()->user()->can('roles.update')) {
                            $btns .= '<a href="' . route('roles.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (
                            auth()->user()->can('roles.delete')
                            && ! in_array($row->name, self::PROTECTED_ROLES)
                            && $row->users_count === 0
                        ) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '" data-name="' . e($row->name) . '"
                                data-url="' . route('roles.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('pages.roles.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan role: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data role tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $permissionGroups = $this->groupedPermissions();

        return view('pages.roles.create', compact('permissionGroups'));
    }

    public function store(RoleRequest $request)
    {
        try {
            $data = $request->validated();

            $role = Role::create(['name' => $data['name']]);
            $role->syncPermissions($data['permissions'] ?? []);

            return redirect()->route('roles.index')->with('success', 'Role berhasil dibuat.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan role: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $role = Role::findOrFail($id);
            $permissionGroups = $this->groupedPermissions();
            $rolePermissions  = $role->permissions->pluck('name')->all();

            $isProtected = in_array($role->name, self::PROTECTED_ROLES);

            return view('pages.roles.edit', compact('role', 'permissionGroups', 'rolePermissions', 'isProtected'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit role: ' . $e->getMessage());
            return redirect()->route('roles.index')->with('error', 'Role tidak ditemukan.');
        }
    }

    public function update(RoleRequest $request, string $id)
    {
        try {
            $role = Role::findOrFail($id);
            $data = $request->validated();

            // Role inti (admin) namanya tidak boleh diubah, tapi permission
            // tetap boleh disesuaikan.
            if (! in_array($role->name, self::PROTECTED_ROLES)) {
                $role->update(['name' => $data['name']]);
            }

            $role->syncPermissions($data['permissions'] ?? []);

            return redirect()->route('roles.index')->with('success', 'Role berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui role: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $role = Role::withCount('users')->findOrFail($id);

            if (in_array($role->name, self::PROTECTED_ROLES)) {
                throw new \Exception("Role \"{$role->name}\" tidak dapat dihapus karena termasuk role inti sistem.");
            }

            if ($role->users_count > 0) {
                throw new \Exception("Role ini masih dipakai oleh {$role->users_count} user, tidak dapat dihapus.");
            }

            $role->delete();

            return redirect()->route('roles.index')->with('success', 'Role berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus role: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kelompokkan permission per modul, dan di dalam tiap modul
     * tampilkan checkbox terpisah untuk setiap aksi CRUD — tidak ada
     * lagi 1 permission gabungan untuk create+update+delete.
     */
    private function groupedPermissions(): array
    {
        $labels = [
            'departments.view'   => 'Lihat Department',
            'departments.create' => 'Tambah Department',
            'departments.update' => 'Edit Department',
            'departments.delete' => 'Hapus Department',

            'warehouses.view'    => 'Lihat Gudang',
            'warehouses.create'  => 'Tambah Gudang',
            'warehouses.update'  => 'Edit Gudang',
            'warehouses.delete'  => 'Hapus Gudang',

            'items.view'   => 'Lihat Item & Kartu Stok',
            'items.create' => 'Tambah Item',
            'items.update' => 'Edit Item',
            'items.delete' => 'Hapus Item',

            'item-locations.view'   => 'Lihat Stok Gudang',
            'item-locations.create' => 'Tambah Stok Gudang',
            'item-locations.update' => 'Edit Stok Gudang',
            'item-locations.delete' => 'Hapus Stok Gudang',

            'transactions.porc.view'   => 'Lihat Transaksi Supply Oil (PORC)',
            'transactions.porc.create' => 'Input Supply Oil (PORC)',
            'transactions.porc.delete' => 'Hapus Transaksi Supply Oil (PORC)',
            'transactions.cons.view'   => 'Lihat Transaksi Pemakaian (CONS)',
            'transactions.cons.create' => 'Input Pemakaian (CONS)',
            'transactions.adj.view'    => 'Lihat Transaksi Adjustment (ADJ)',
            'transactions.adj.create'  => 'Input Adjustment (ADJ)',

            'transfer-requests.view'     => 'Lihat Transfer Request',
            'transfer-requests.create'   => 'Buat Transfer Request',
            'transfer-requests.cancel'   => 'Batalkan Transfer Request Sendiri',
            'transfer-requests.approve'  => 'Approve Transfer Request (IMC)',
            'transfer-requests.reject'   => 'Reject Transfer Request (IMC)',
            'transfer-requests.receive'  => 'Konfirmasi Terima Transfer',

            'users.view'   => 'Lihat User',
            'users.create' => 'Tambah User',
            'users.update' => 'Edit User',
            'users.delete' => 'Hapus User',

            'roles.view'   => 'Lihat Role',
            'roles.create' => 'Tambah Role',
            'roles.update' => 'Edit Role',
            'roles.delete' => 'Hapus Role',

            'reports.view' => 'Lihat Laporan',
        ];

        $groups = [
            'Department'       => ['departments.view', 'departments.create', 'departments.update', 'departments.delete'],
            'Gudang'           => ['warehouses.view', 'warehouses.create', 'warehouses.update', 'warehouses.delete'],
            'Item Master'      => ['items.view', 'items.create', 'items.update', 'items.delete'],
            'Stok Gudang'      => ['item-locations.view', 'item-locations.create', 'item-locations.update', 'item-locations.delete'],
            'Transaksi - Supply Oil (PORC)'  => ['transactions.porc.view', 'transactions.porc.create', 'transactions.porc.delete'],
            'Transaksi - Pemakaian (CONS)'   => ['transactions.cons.view', 'transactions.cons.create'],
            'Transaksi - Adjustment (ADJ)'   => ['transactions.adj.view', 'transactions.adj.create'],
            'Transfer Request' => [
                'transfer-requests.view',
                'transfer-requests.create',
                'transfer-requests.cancel',
                'transfer-requests.approve',
                'transfer-requests.reject',
                'transfer-requests.receive',
            ],
            'User Management'  => ['users.view', 'users.create', 'users.update', 'users.delete'],
            'Role Management'  => ['roles.view', 'roles.create', 'roles.update', 'roles.delete'],
            'Laporan'          => ['reports.view'],
        ];

        $existingNames = Permission::pluck('name')->all();

        $result = [];
        foreach ($groups as $groupLabel => $names) {
            $result[$groupLabel] = collect($names)
                ->filter(fn($name) => in_array($name, $existingNames))
                ->map(fn($name) => ['name' => $name, 'label' => $labels[$name] ?? $name])
                ->values()
                ->all();
        }

        return $result;
    }
}
