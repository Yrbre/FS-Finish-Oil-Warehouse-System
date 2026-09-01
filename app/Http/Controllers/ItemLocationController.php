<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Http\Requests\DisposalRequest;
use App\Http\Requests\ItemLocationRequest;
use App\Services\DisposalService;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ItemLocationController extends Controller
{
    protected ItemLocationServiceInterface $itemLocationService;
    protected ItemServiceInterface $itemService;
    protected WarehouseServiceInterface $warehouseService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected DepartmentServiceInterface $departmentService;
    protected DisposalService $disposalService;

    public function __construct(
        ItemLocationServiceInterface $itemLocationService,
        ItemServiceInterface $itemService,
        WarehouseServiceInterface $warehouseService,
        StockLedgerServiceInterface $stockLedgerService,
        DepartmentServiceInterface $departmentService,
        DisposalService $disposalService
    ) {
        $this->itemLocationService = $itemLocationService;
        $this->itemService         = $itemService;
        $this->warehouseService    = $warehouseService;
        $this->stockLedgerService  = $stockLedgerService;
        $this->departmentService   = $departmentService;
        $this->disposalService     = $disposalService;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            // department_id nullable — tanpa ?-> baris ini fatal error
            // untuk user yang belum terdaftar di department manapun.
            $isImc = $user->department?->code === Department::CODE_IMC
                || $user->hasRole('admin');

            if ($request->ajax()) {
                return $isImc
                    ? $this->lotTable($request)
                    : $this->summaryTable($user);
            }

            $items = $this->itemService->getAll()->get();

            $warehouses = $isImc ? $this->warehouseService->getAll()->get() : collect();
            $departments = $isImc ? $this->departmentService->getAll()->get() : collect();

            return view('pages.item_locations.index', compact('items', 'warehouses', 'departments', 'isImc'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan item location: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data stok gudang tidak dapat ditampilkan.');
        }
    }

    /**
     * Rincian per lot — untuk IMC & admin yang mengelola gudang.
     */
    private function lotTable(Request $request)
    {
        $itemLocations = $this->itemLocationService->getAll();

        if ($request->item_id) {
            $itemLocations->where('item_id', $request->item_id);
        }
        if ($request->warehouse_id) {
            $itemLocations->where('warehouse_id', $request->warehouse_id);
        }
        if ($request->demander_id) {
            $itemLocations->where('demander_id', $request->demander_id);
        }

        return DataTables::of($itemLocations)
            ->addIndexColumn()
            ->addColumn('item', fn($row) => $row->item->item_no . ' - ' . $row->item->item_desc)
            ->addColumn('warehouse', fn($row) => $row->warehouse->name . ' - ' . $row->warehouse->tag)
            ->addColumn('demander', fn($row) => $row->demander->code ?? '-')
            ->addColumn('receiving_lot', fn($row) => $row->receiving_lot ?? '-')
            ->addColumn('exp_date', fn($row) => $row->exp_date ? $row->exp_date->format('M Y') : '-')
            ->addColumn('package', function ($row) {
                // qty_package tidak di-update saat CONS — yang benar
                // adalah hasil bagi berat, lewat accessor.
                return number_format($row->qty_package_display, 2, ',', '.') . ' ' .
                    ($row->package ?? 'pkg') .
                    '<br><small class="text-muted">@ ' .
                    number_format((float) $row->qty_perpackage, 2, ',', '.') . ' kg</small>';
            })
            ->addColumn('qty_weight', fn($row) => number_format((float) $row->qty_weight, 2, ',', '.') . ' ' . $row->item->item_uom)
            ->addColumn('action', function ($row) {
                $btns = '';

                if (auth()->user()->can('item-locations.update')) {
                    $btns .= '<a href="' . route('item-locations.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                }

                if (auth()->user()->can('item-locations.dispose') && (float) $row->qty_weight > 0) {
                    $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-dispose"
                        data-id="' . $row->id . '"
                        data-name="' . e($row->receiving_lot ?? $row->item->item_desc) . '"
                        data-qty="' . number_format((float) $row->qty_weight, 2, ',', '.') . '">Buang</button>';
                }

                return $btns ?: '<span class="text-muted">-</span>';
            })
            ->rawColumns(['package', 'action'])
            ->make(true);
    }

    /**
     * Rekap per item — untuk staff. Memisahkan stok yang sudah ada
     * di gudang sendiri dari yang masih dititipkan di IMC, supaya
     * staff tahu kapan perlu membuat transfer request.
     */
    private function summaryTable($user)
    {
        $imcWarehouseIds = $this->warehouseService->getIdsByDepartmentCode(Department::CODE_IMC);

        $rows = $this->itemLocationService->getDemanderStockSummary(
            (int) $user->department_id,
            $imcWarehouseIds
        );

        return DataTables::of($rows)
            ->addIndexColumn()
            ->addColumn('item', fn($row) => '<strong>' . e($row->item_no) . '</strong><br>'
                . '<small class="text-muted">' . e($row->item_desc) . '</small>')
            ->addColumn('imc_stock', fn($row) => number_format($row->imc_stock, 2, ',', '.') . ' ' . $row->item_uom)
            ->addColumn('local_stock', function ($row) {
                $text = number_format($row->local_stock, 2, ',', '.') . ' ' . $row->item_uom;

                // Stok di gudang habis padahal masih ada titipan di IMC
                // — pertanda perlu segera membuat transfer request.
                if ($row->local_stock <= 0 && $row->imc_stock > 0) {
                    return '<span class="text-danger">' . $text . '</span>';
                }

                return $text;
            })
            ->addColumn('total_stock', function ($row) {
                $text = '<strong>' . number_format($row->total_stock, 2, ',', '.') . ' ' . $row->item_uom . '</strong>';

                if ($row->min_stock !== null && $row->total_stock < $row->min_stock) {
                    return $text . '<br><span class="badge badge-warning">Di bawah minimum</span>';
                }

                return $text;
            })
            ->addColumn('min_stock', fn($row) => $row->min_stock !== null
                ? number_format($row->min_stock, 2, ',', '.')
                : '<span class="text-muted">-</span>')
            ->rawColumns(['item', 'local_stock', 'total_stock', 'min_stock'])
            ->make(true);
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

    public function dispose(DisposalRequest $request, string $id)
    {
        try {
            $this->disposalService->dispose(
                (int) $id,
                auth()->id(),
                $request->validated()['disposal_reason']
            );

            return redirect()->route('item-locations.index')
                ->with('success', 'Lot berhasil dibuang. Stok dikeluarkan dari perhitungan.');
        } catch (\Exception $e) {
            Log::error('Gagal membuang lot: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }
}
