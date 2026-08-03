<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Services\Interfaces\DepartmentServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class DepartmentController extends Controller
{
    protected DepartmentServiceInterface $departmentService;

    public function __construct(DepartmentServiceInterface $departmentService)
    {
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $departments = $this->departmentService->getAll();

                return DataTables::of($departments)
                    ->addIndexColumn()
                    ->addColumn('warehouse_count', fn($row) => $row->warehouses()->count())
                    ->addColumn('action', function ($row) {
                        $btns = '';

                        if (auth()->user()->can('departments.update')) {
                            $btns .= '<a href="' . route('departments.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (auth()->user()->can('departments.delete')) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '"
                                data-url="' . route('departments.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('pages.departments.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan department: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data department tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        return view('pages.departments.create');
    }

    public function store(DepartmentRequest $request)
    {
        try {
            $this->departmentService->create($request->validated());

            return redirect()->route('departments.index')
                ->with('success', 'Department berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan department: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $department = $this->departmentService->getById((int) $id);

            return view('pages.departments.edit', compact('department'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit department: ' . $e->getMessage());

            return redirect()->route('departments.index')->with('error', 'Department tidak ditemukan.');
        }
    }

    public function update(DepartmentRequest $request, string $id)
    {
        try {
            $this->departmentService->update((int) $id, $request->validated());

            return redirect()->route('departments.index')
                ->with('success', 'Department berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui department: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->departmentService->delete((int) $id);

            return redirect()->route('departments.index')
                ->with('success', 'Department berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus department: ' . $e->getMessage());

            // Pesan dari service informatif untuk user
            // (misal: masih punya warehouse), jadi ditampilkan apa adanya
            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
