<?php

namespace App\Http\Controllers;

use App\Http\Requests\RelocationRequest;
use App\Models\Department;
use App\Models\ItemLocation;
use App\Models\ItemRelocation;
use App\Services\Interfaces\WarehouseServiceInterface;
use App\Services\RelocationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class RelocationController extends Controller
{
    public function __construct(
        protected RelocationService $relocationService,
        protected WarehouseServiceInterface $warehouseService,
    ) {}

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $relocations = ItemRelocation::with(['item', 'fromWarehouse', 'toWarehouse', 'demander', 'mover'])
                    ->latest('moved_at');

                return DataTables::of($relocations)
                    ->addIndexColumn()
                    ->addColumn('item', fn($row) => $row->item->item_no . ' - ' . $row->item->item_desc)
                    // Tag disnapshot saat pemindahan — pakai itu, bukan
                    // tag gudang sekarang yang mungkin sudah berubah.
                    ->addColumn('from', fn($row) => $row->fromWarehouse->name . ' - ' . ($row->from_tag ?? '-'))
                    ->addColumn('to', fn($row) => $row->toWarehouse->name . ' - ' . ($row->to_tag ?? '-'))
                    ->addColumn('demander', fn($row) => $row->demander->code ?? '-')
                    ->addColumn('qty', fn($row) => (int) $row->package_moved . ' pkg<br><small class="text-muted">'
                        . number_format((float) $row->qty_moved, 2, ',', '.') . ' kg</small>')
                    ->addColumn('moved', fn($row) => $row->moved_at->format('d-m-Y H:i') . '<br><small class="text-muted">'
                        . ($row->mover->name ?? '-') . '</small>')
                    ->rawColumns(['qty', 'moved'])
                    ->make(true);
            }

            return view('pages.relocations.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan riwayat pemindahan: ' . $e->getMessage());

            return redirect()->back()->with('error', 'Data pemindahan tidak dapat ditampilkan.');
        }
    }

    public function create()
    {
        $imcWarehouseIds = $this->warehouseService->getIdsByDepartmentCode(Department::CODE_IMC);

        $lots = ItemLocation::with(['item', 'warehouse', 'demander'])
            ->whereIn('warehouse_id', $imcWarehouseIds)
            ->available()
            ->orderBy('item_id')
            ->get();

        $warehouses = $this->warehouseService->getAll()
            ->whereHas('department', fn($q) => $q->where('code', Department::CODE_IMC))
            ->get();

        return view('pages.relocations.create', compact('lots', 'warehouses'));
    }

    public function store(RelocationRequest $request)
    {
        try {
            $data = $request->validated();

            $this->relocationService->relocate(
                (int) $data['item_location_id'],
                (int) $data['to_warehouse_id'],
                (float) $data['package_moved'],
                auth()->id(),
                $data['reason'] ?? null
            );

            return redirect()->route('relocations.index')
                ->with('success', 'Barang berhasil dipindahkan.');
        } catch (\Exception $e) {
            Log::error('Gagal memindahkan barang: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }
}
