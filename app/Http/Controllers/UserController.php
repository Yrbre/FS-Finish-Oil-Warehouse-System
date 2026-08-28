<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    protected UserServiceInterface $userService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        UserServiceInterface $userService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->userService       = $userService;
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $users = $this->userService->getAll()->with('transferApprover');

                return DataTables::of($users)
                    ->addIndexColumn()
                    ->addColumn('department', fn($row) => $row->department->name ?? '-')
                    ->addColumn('role', fn($row) => strtoupper($row->getRoleNames()->first() ?? '-'))
                    ->addColumn('is_approver', fn($row) => $row->isTransferApprover()
                        ? '<span class="badge badge-success">IMC Approver</span>'
                        : '-')
                    ->addColumn('action', function ($row) {
                        $btns = '';

                        if (auth()->user()->can('users.update')) {
                            $btns .= '<a href="' . route('users.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (auth()->user()->can('users.delete') && $row->id !== auth()->id()) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '" data-name="' . e($row->name) . '"
                                data-url="' . route('users.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['is_approver', 'action'])
                    ->make(true);
            }

            return view('pages.users.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan user: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data user tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $departments = $this->departmentService->getAll()->get();
        $roles       = Role::pluck('name');

        return view('pages.users.create', compact('departments', 'roles'));
    }

    public function store(UserRequest $request)
    {
        try {
            $this->userService->create($request->validated());

            return redirect()->route('users.index')->with('success', 'User berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan user: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $user        = $this->userService->getById((int) $id);
            $departments = $this->departmentService->getAll()->get();
            $roles       = Role::pluck('name');

            return view('pages.users.edit', compact('user', 'departments', 'roles'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit user: ' . $e->getMessage());
            return redirect()->route('users.index')->with('error', 'User tidak ditemukan.');
        }
    }

    public function update(UserRequest $request, string $id)
    {
        try {
            $this->userService->update((int) $id, $request->validated());

            return redirect()->route('users.index')->with('success', 'User berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui user: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            if ((int) $id === auth()->id()) {
                throw new \Exception("Tidak dapat menghapus akun sendiri.");
            }

            $this->userService->delete((int) $id);

            return redirect()->route('users.index')->with('success', 'User berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus user: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
