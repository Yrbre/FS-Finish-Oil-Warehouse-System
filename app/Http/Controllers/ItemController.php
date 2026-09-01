<?php

namespace App\Http\Controllers;

use App\Http\Requests\ItemRequest;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class ItemController extends Controller
{
    protected ItemServiceInterface $itemService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected WarehouseServiceInterface $warehouseService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        ItemServiceInterface $itemService,
        StockLedgerServiceInterface $stockLedgerService,
        WarehouseServiceInterface $warehouseService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->itemService         = $itemService;
        $this->stockLedgerService  = $stockLedgerService;
        $this->warehouseService    = $warehouseService;
        $this->departmentService   = $departmentService;
    }

    public function index(Request $request)
    {
        try {
            $user = auth()->user();

            // IMC & admin melihat total seluruh department. Staff
            // melihat miliknya sendiri, dipisah antara yang masih
            // dititipkan di IMC dan yang sudah di gudang sendiri.
            $isImc = $user->department?->code === \App\Models\Department::CODE_IMC
                || $user->hasRole('admin');

            if ($request->ajax()) {
                $items = $this->itemService->getAll();

                $imcIds = $this->warehouseService->getIdsByDepartmentCode(\App\Models\Department::CODE_IMC);

                if ($isImc) {
                    // Yang dikelola IMC adalah gudangnya sendiri —
                    // stok yang sudah diserahkan ke department bukan
                    // lagi tanggung jawabnya.
                    $items->withSum(
                        ['itemLocations as imc_stock' => fn($q) => $q
                            ->whereNull('disposed_at')->where('qty_weight', '>', 0)
                            ->whereIn('warehouse_id', $imcIds)],
                        'qty_weight'
                    );
                } else {
                    $demanderId = (int) $user->department_id;

                    $items->withSum(
                        ['itemLocations as imc_stock' => fn($q) => $q
                            ->whereNull('disposed_at')->where('qty_weight', '>', 0)
                            ->where('demander_id', $demanderId)
                            ->whereIn('warehouse_id', $imcIds)],
                        'qty_weight'
                    )->withSum(
                        ['itemLocations as local_stock' => fn($q) => $q
                            ->whereNull('disposed_at')->where('qty_weight', '>', 0)
                            ->where('demander_id', $demanderId)
                            ->whereNotIn('warehouse_id', $imcIds)],
                        'qty_weight'
                    );
                }

                $dt = DataTables::of($items)->addIndexColumn();

                $dt->addColumn('imc_stock', fn($row) =>
                number_format((float) ($row->imc_stock ?? 0), 2, ',', '.') . ' ' . $row->item_uom);

                if (! $isImc) {
                    $dt->addColumn('local_stock', function ($row) {
                        $local = (float) ($row->local_stock ?? 0);
                        $imc   = (float) ($row->imc_stock ?? 0);
                        $text  = number_format($local, 2, ',', '.') . ' ' . $row->item_uom;

                        // Habis di gudang padahal masih ada titipan
                        // di IMC — waktunya buat transfer request.
                        return ($local <= 0 && $imc > 0)
                            ? '<span class="text-danger">' . $text . '</span>'
                            : $text;
                    })
                        ->addColumn('total_stock', function ($row) {
                            $total = (float) ($row->imc_stock ?? 0) + (float) ($row->local_stock ?? 0);
                            $text  = '<strong>' . number_format($total, 2, ',', '.') . ' ' . $row->item_uom . '</strong>';

                            if ($row->min_stock !== null && $total < (float) $row->min_stock) {
                                return $text . '<br><span class="badge badge-warning">Di bawah minimum</span>';
                            }

                            return $text;
                        });
                }

                $dt->addColumn('action', function ($row) {
                    $btns = '<a href="' . route('items.detail', $row->id) . '" class="btn btn-sm btn-info">Kartu Stok</a>';

                    if (auth()->user()->can('items.update')) {
                        $btns .= ' <a href="' . route('items.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                    }

                    if (auth()->user()->can('items.delete')) {
                        $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                            data-id="' . $row->id . '"
                            data-name="' . e($row->item_desc) . '"
                            data-url="' . route('items.destroy', $row->id) . '">Hapus</button>';
                    }

                    return $btns;
                });

                return $dt->rawColumns(['action', 'local_stock', 'total_stock'])->make(true);
            }

            return view('pages.items.index', compact('isImc'));
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
     * Kartu stok bulanan.
     *
     * - User dengan permission 'manage-items' (admin/IMC): kartu lengkap,
     *   bisa pilih department & warehouse manapun, semua jenis transaksi.
     * - User lain yang cuma punya 'create-transaction' (staff): otomatis
     *   ter-scope ke department dia sendiri, HANYA menghitung Transfer-in,
     *   CONS, dan ADJ (PORC & Transfer-out diabaikan).
     */
    public function detail(string $id, Request $request)
    {
        try {
            $month = (int) $request->get('month', now()->month);
            $year  = (int) $request->get('year', now()->year);
            $item  = $this->itemService->getById((int) $id);

            // Penanda "akses penuh" (admin/IMC, bisa lihat semua department)
            // dipakai item-locations.view, karena hanya role yang benar-benar
            // mengelola gudang secara luas yang punya permission ini —
            // staff tidak pernah diberi item-locations.view.
            $isFullAccess = auth()->user()->can('item-locations.view');

            if ($isFullAccess) {
                $departments = $this->departmentService->getAll()->get();
                $warehouses  = $this->warehouseService->getAll()->get();

                $departmentId = $request->get('department_id') ?: $departments->first()?->id;
                $warehouseId  = $request->get('warehouse_id');

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
            } else {
                // Staff: department terkunci ke department dia sendiri,
                // tapi warehouse di dalamnya boleh dipilih bebas.
                $departmentId = auth()->user()->department_id;

                $departmentWarehouses = $departmentId
                    ? $this->warehouseService->getByDepartment((int) $departmentId)
                    : collect();

                if ($departmentWarehouses->isEmpty()) {
                    throw new \Exception("Anda belum terdaftar di department manapun yang memiliki gudang.");
                }

                $warehouseId = $request->get('warehouse_id');

                // Validasi: gudang yang dipilih harus milik department staff
                // sendiri — cegah staff mengetik warehouse_id department lain
                // langsung lewat URL.
                if ($warehouseId && ! $departmentWarehouses->contains('id', (int) $warehouseId)) {
                    throw new \Exception("Gudang yang dipilih bukan milik department Anda.");
                }

                $warehouseIds = $warehouseId
                    ? [(int) $warehouseId]
                    : $departmentWarehouses->pluck('id')->all();

                $departments = collect(); // staff tidak perlu pilih department
                $warehouses  = $departmentWarehouses; // untuk dropdown, sudah ter-scope

                $stockCard = $this->stockLedgerService->getStaffMonthlyStockCard(
                    (int) $id,
                    $month,
                    $year,
                    $warehouseIds
                );
            }

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
                'warehouseId',
                'isFullAccess'
            ));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan kartu stok: ' . $e->getMessage());

            return redirect()->route('items.index')->with('error', 'Kartu stok tidak dapat ditampilkan: ' . $e->getMessage());
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
