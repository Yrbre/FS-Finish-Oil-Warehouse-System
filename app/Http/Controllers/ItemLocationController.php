<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemLocationRequest;
use App\Models\StockLedger;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ItemLocationController extends Controller
{
    protected ItemLocationServiceInterface $itemLocationService;
    protected ItemServiceInterface $itemService;
    protected WarehouseServiceInterface $warehouseService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        ItemLocationServiceInterface $itemLocationService,
        ItemServiceInterface $itemService,
        WarehouseServiceInterface $warehouseService,
        StockLedgerServiceInterface $stockLedgerService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->itemLocationService = $itemLocationService;
        $this->itemService         = $itemService;
        $this->warehouseService    = $warehouseService;
        $this->stockLedgerService  = $stockLedgerService;
        $this->departmentService   = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $itemLocations = $this->itemLocationService->getAll();

                if ($request->item_id) {
                    $itemLocations->where('item_id', $request->item_id);
                }
                if ($request->warehouse_id) {
                    $itemLocations->where('warehouse_id', $request->warehouse_id);
                }

                return DataTables::of($itemLocations)
                    ->addIndexColumn()
                    ->addColumn('item', fn($row) => $row->item->item_desc)
                    ->addColumn('warehouse', fn($row) => $row->warehouse->name)
                    ->addColumn('exp_date', fn($row) => $row->exp_date ? $row->exp_date->format('d-m-Y') : '-')
                    ->addColumn('qty_weight', fn($row) => number_format((float) $row->qty_weight, 2, ',', '.') . ' ' . $row->item->item_uom)
                    ->addColumn('action', function ($row) {
                        return '
                            <a href="' . route('item-locations.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>
                            <button type="button" class="btn btn-sm btn-danger btn-delete"
                                data-id="' . $row->id . '"
                                data-name="' . e($row->vendor_lot ?? $row->item->item_desc) . '"
                                data-url="' . route('item-locations.destroy', $row->id) . '">Hapus</button>
                        ';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            $items      = $this->itemService->getAll()->get();
            $warehouses = $this->warehouseService->getAll()->get();

            return view('pages.item_locations.index', compact('items', 'warehouses'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan item location: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data stok gudang tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $items      = $this->itemService->getAll()->get();
        $warehouses = $this->warehouseService->getAll()->get();
        $departments = $this->departmentService->getAll()->get();

        return view('pages.item_locations.create', compact('items', 'warehouses', 'departments'));
    }

    public function store(ItemLocationRequest $request)
    {
        try {
            DB::transaction(function () use ($request) {
                $data = $request->validated();
                $data['is_warehouse_stock'] = true;

                // bb_qty = stok item+gudang ini SEBELUM stok baru ditambahkan
                $bbQty = $this->itemLocationService->getTotalStock(
                    (int) $data['item_id'],
                    (int) $data['warehouse_id']
                );
                $qty   = (float) $data['qty_weight'];
                $ebQty = $bbQty + $qty;

                $itemLocation = $this->itemLocationService->create($data);

                // Arsipkan sebagai stok pembuka (bukan dari vendor/PORC),
                // supaya kartu stok bulan pertama tidak menampilkan 0
                // padahal fisiknya sudah ada barang.
                $this->stockLedgerService->record([
                    'item_id'      => $data['item_id'],
                    'warehouse_id' => $data['warehouse_id'],
                    'trans_date'   => $data['received_date'] ?? now()->toDateString(),
                    'in_qty'       => $qty,
                    'out_qty'      => 0,
                    'bb_qty'       => $bbQty,
                    'eb_qty'       => $ebQty,
                    'doc_type'     => StockLedger::DOC_OPENING,
                    'ref_type'     => StockLedger::REF_OPENING,
                    'ref_id'       => $itemLocation->id, // nunjuk ke lot yang baru dibuat
                ]);
            });

            return redirect()->route('item-locations.index')
                ->with('success', 'Stok gudang berhasil ditambahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan item location: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function edit(string $id)
    {
        try {
            $itemLocation = $this->itemLocationService->getById((int) $id);
            $items        = $this->itemService->getAll()->get();
            $warehouses   = $this->warehouseService->getAll()->get();
            $departments  = $this->departmentService->getAll()->get();

            return view('pages.item_locations.edit', compact('itemLocation', 'items', 'warehouses', 'departments'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit item location: ' . $e->getMessage());

            return redirect()->route('item-locations.index')->with('error', 'Data stok tidak ditemukan.');
        }
    }

    public function update(ItemLocationRequest $request, string $id)
    {
        try {
            $this->itemLocationService->update((int) $id, $request->validated());

            return redirect()->route('item-locations.index')
                ->with('success', 'Stok gudang berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui item location: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function destroy(string $id)
    {
        try {
            $this->itemLocationService->delete((int) $id);

            return redirect()->route('item-locations.index')
                ->with('success', 'Stok gudang berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus item location: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
