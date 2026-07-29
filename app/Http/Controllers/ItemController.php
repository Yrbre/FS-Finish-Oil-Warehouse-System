<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ItemController extends Controller
{
    protected ItemServiceInterface $itemService;
    protected ItemLocationServiceInterface $itemLocationService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected WarehouseServiceInterface $warehouseService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        ItemServiceInterface $itemService,
        ItemLocationServiceInterface $itemLocationService,
        StockLedgerServiceInterface $stockLedgerService,
        WarehouseServiceInterface $warehouseService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->itemService         = $itemService;
        $this->itemLocationService = $itemLocationService;
        $this->stockLedgerService  = $stockLedgerService;
        $this->warehouseService    = $warehouseService;
        $this->departmentService   = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $items = $this->itemService->getAll();

                return DataTables::of($items)
                    ->addIndexColumn()
                    ->addColumn('total_stock', function ($row) {
                        $stock = $this->itemLocationService->getTotalStockAllWarehouses($row->id);

                        return number_format($stock, 2, ',', '.') . ' ' . $row->item_uom;
                    })
                    ->addColumn('action', function ($row) {
                        return '
                            <a href="' . route('items.detail', $row->id) . '" class="btn btn-sm btn-info">Kartu Stok</a>
                            <a href="' . route('items.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->item_desc) . '"
                                data-url="' . route('items.destroy', $row->id) . '">Hapus</button>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            return view('pages.items.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan item: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data item tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        return view('pages.items.create');
    }

    public function store(ItemRequest $request)
    {
        try {
            $this->itemService->create($request->validated());

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan item: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $item = $this->itemService->getById((int) $id);

            return view('pages.items.edit', compact('item'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit item: ' . $e->getMessage());

            return redirect()->route('items.index')->with('error', 'Item tidak ditemukan.');
        }
    }

    public function update(ItemRequest $request, string $id)
    {
        try {
            $this->itemService->update((int) $id, $request->validated());

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui item: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->itemService->delete((int) $id);

            return redirect()->route('items.index')
                ->with('success', 'Item berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus item: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Kartu stok bulanan — mutasi harian pemasukan & pengeluaran.
     * Bisa difilter per gudang, atau gabungan semua gudang.
     */
    public function detail(string $id, Request $request)
    {
        try {
            $month = (int) $request->get('month', now()->month);
            $year  = (int) $request->get('year', now()->year);

            $item       = $this->itemService->getById((int) $id);
            $departments = $this->departmentService->getAll()->get();
            $warehouses  = $this->warehouseService->getAll()->get();

            // Default ke department pertama kalau belum ada yang dipilih —
            // supaya tidak pernah jatuh ke "sum semua gudang tanpa batas".
            $departmentId = $request->get('department_id') ?: $departments->first()?->id;
            $warehouseId  = $request->get('warehouse_id');

            // Resolve warehouse mana saja yang ikut dijumlah:
            // - kalau user pilih 1 gudang spesifik → cuma gudang itu
            // - kalau tidak → semua gudang milik department yang dipilih
            if ($warehouseId) {
                $warehouseIds = [(int) $warehouseId];
            } elseif ($departmentId) {
                $warehouseIds = $this->warehouseService
                    ->getByDepartment((int) $departmentId)
                    ->pluck('id')
                    ->all();
            } else {
                $warehouseIds = null;
            }

            $stockCard = $this->stockLedgerService->getMonthlyStockCard(
                (int) $id,
                $month,
                $year,
                $warehouseIds
            );


            $summary = (object) [
                'total_in'  => $stockCard->sum('in_qty'),
                'total_out' => $stockCard->sum('out_qty'),
                'total_adj' => $stockCard->sum('adj_qty'),
                'closing'   => $stockCard->last()->eb_qty,
            ];

            return view('pages.items.detail', compact(
                'item',
                'stockCard',
                'summary',
                'departments',
                'warehouses',
                'month',
                'year',
                'departmentId',
                'warehouseId'
            ));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan kartu stok: ' . $e->getMessage());

            return redirect()->route('items.index')->with('error', 'Kartu stok tidak dapat ditampilkan.');
        }
    }

    /**
     * AJAX: total stok item di gudang tertentu.
     */
    public function getStock(Request $request)
    {
        $request->validate([
            'item_id'      => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $item  = $this->itemService->getById((int) $request->item_id);
        $stock = $this->itemLocationService->getTotalStock(
            (int) $request->item_id,
            (int) $request->warehouse_id
        );

        return response()->json([
            'stock' => $stock,
            'uom'   => $item->item_uom,
        ]);
    }
}
