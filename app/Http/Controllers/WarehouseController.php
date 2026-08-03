<?php

namespace App\Http\Controllers;

use App\Http\Requests\WarehouseRequest;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class WarehouseController extends Controller
{
    protected WarehouseServiceInterface $warehouseService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        WarehouseServiceInterface $warehouseService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->warehouseService  = $warehouseService;
        $this->departmentService = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $warehouses = $this->warehouseService->getAll();

                if ($request->department_id) {
                    $warehouses->where('department_id', $request->department_id);
                }

                return DataTables::of($warehouses)
                    ->addIndexColumn()
                    ->addColumn('department', fn($row) => $row->department->code . ' - ' . $row->department->name)
                    ->addColumn('action', function ($row) {
                        $btns = '';

                        if (auth()->user()->can('warehouses.update')) {
                            $btns .= '<a href="' . route('warehouses.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (auth()->user()->can('warehouses.delete')) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->name) . '"
                                data-url="' . route('warehouses.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            $departments = $this->departmentService->getAll()->get();

            return view('pages.warehouses.index', compact('departments'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan warehouse: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data gudang tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $departments = $this->departmentService->getAll()->get();

        return view('pages.warehouses.create', compact('departments'));
    }

    public function store(WarehouseRequest $request)
    {
        try {
            $this->warehouseService->create($request->validated());

            return redirect()->route('warehouses.index')
                ->with('success', 'Gudang berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan warehouse: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $warehouse   = $this->warehouseService->getById((int) $id);
            $departments = $this->departmentService->getAll()->get();

            return view('pages.warehouses.edit', compact('warehouse', 'departments'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit warehouse: ' . $e->getMessage());

            return redirect()->route('warehouses.index')->with('error', 'Gudang tidak ditemukan.');
        }
    }

    public function update(WarehouseRequest $request, string $id)
    {
        try {
            $this->warehouseService->update((int) $id, $request->validated());

            return redirect()->route('warehouses.index')
                ->with('success', 'Gudang berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui warehouse: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->warehouseService->delete((int) $id);

            return redirect()->route('warehouses.index')
                ->with('success', 'Gudang berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus warehouse: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
