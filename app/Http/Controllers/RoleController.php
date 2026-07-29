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
                        $btns = '<a href="' . route('roles.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';

                        if (! in_array($row->name, self::PROTECTED_ROLES) && $row->users_count === 0) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '" data-name="' . e($row->name) . '"
                                data-url="' . route('roles.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns;
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
     * Kelompokkan permission supaya checkbox di form lebih rapi,
     * bukan daftar datar 11 item.
     */
    private function groupedPermissions(): array
    {
        $labels = [
            'manage-departments'      => 'Kelola Department',
            'manage-warehouses'       => 'Kelola Gudang',
            'manage-items'            => 'Kelola Item Master',
            'manage-item-locations'   => 'Kelola Stok Gudang',
            'create-transaction'      => 'Input Transaksi (PORC/CONS/ADJ)',
            'manage-transfer-request' => 'Buat & Lihat Transfer Request',
            'approve-transfer'        => 'Approve/Reject Transfer (IMC)',
            'receive-transfer'        => 'Konfirmasi Terima Transfer',
            'manage-users'            => 'Kelola User',
            'manage-roles'            => 'Kelola Role & Permission',
            'view-reports'            => 'Lihat Laporan',
        ];

        $groups = [
            'Master Data' => ['manage-departments', 'manage-warehouses', 'manage-items'],
            'Inventory'   => ['manage-item-locations'],
            'Transaksi'   => ['create-transaction'],
            'Transfer'    => ['manage-transfer-request', 'approve-transfer', 'receive-transfer'],
            'Laporan'     => ['view-reports'],
            'User Management' => ['manage-users', 'manage-roles'],
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
