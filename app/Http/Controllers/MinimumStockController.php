<?php

namespace App\Http\Controllers;

use App\Http\Requests\MinimumStockRequest;
use App\Models\Department;
use App\Models\MinimumStock;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class MinimumStockController extends Controller
{
    public function __construct(
        protected ItemServiceInterface $itemService,
        protected DepartmentServiceInterface $departmentService,
    ) {}

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $rows = MinimumStock::with(['item', 'department']);

                if ($request->department_id) {
                    $rows->where('department_id', $request->department_id);
                }

                return DataTables::of($rows)
                    ->addIndexColumn()
                    ->addColumn('item', fn($row) => $row->item->item_no . ' - ' . $row->item->item_desc)
                    ->addColumn('department', fn($row) => $row->department->code . ' - ' . $row->department->name)
                    ->addColumn('min_stock', fn($row) => number_format((float) $row->min_stock, 2, ',', '.')
                        . ' ' . $row->item->item_uom)
                    ->addColumn('status', fn($row) => $row->is_active
                        ? '<span class="badge badge-success">Aktif</span>'
                        : '<span class="badge badge-secondary">Nonaktif</span>')
                    ->addColumn('action', function ($row) {
                        $btns = '';

                        if (auth()->user()->can('minimum-stocks.update')) {
                            $btns .= '<a href="' . route('minimum-stocks.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (auth()->user()->can('minimum-stocks.delete')) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-name="' . e($row->item->item_no . ' / ' . $row->department->code) . '"
                                data-url="' . route('minimum-stocks.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['status', 'action'])
                    ->make(true);
            }

            $departments = $this->departmentService->getAll()->get();

            return view('pages.minimum_stocks.index', compact('departments'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan minimum stock: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data minimum stock tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $items = $this->itemService->getAll()->get();

        // IMC tidak memiliki stok — hanya menyimpankan, jadi tidak
        // punya ambang minimum sendiri.
        $departments = $this->departmentService->getAll()
            ->where('code', '!=', Department::CODE_IMC)
            ->get();

        return view('pages.minimum_stocks.create', compact('items', 'departments'));
    }

    public function store(MinimumStockRequest $request)
    {
        try {
            MinimumStock::create($request->validated() + ['created_by' => auth()->id()]);

            return redirect()->route('minimum-stocks.index')
                ->with('success', 'Minimum stock berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan minimum stock: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $minimumStock = MinimumStock::findOrFail($id);
            $items        = $this->itemService->getAll()->get();

            $departments = $this->departmentService->getAll()
                ->where('code', '!=', Department::CODE_IMC)
                ->get();

            return view('pages.minimum_stocks.edit', compact('minimumStock', 'items', 'departments'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit minimum stock: ' . $e->getMessage());

            return redirect()->route('minimum-stocks.index')->with('error', 'Data tidak ditemukan.');
        }
    }

    public function update(MinimumStockRequest $request, string $id)
    {
        try {
            MinimumStock::findOrFail($id)->update($request->validated());

            return redirect()->route('minimum-stocks.index')
                ->with('success', 'Minimum stock berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui minimum stock: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            MinimumStock::findOrFail($id)->delete();

            return redirect()->route('minimum-stocks.index')
                ->with('success', 'Minimum stock berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus minimum stock: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
