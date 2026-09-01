<?php

namespace App\Http\Controllers;

use App\Http\Requests\AdjTransactionRequest;
use App\Http\Requests\ConsTransactionRequest;
use App\Http\Requests\PorcTransactionRequest;
use App\Http\Requests\PorcUpdateRequest;
use App\Models\Transaction;
use App\Services\Interfaces\DepartmentServiceInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TransactionController extends Controller
{
    protected TransactionServiceInterface $transactionService;
    protected ItemServiceInterface $itemService;
    protected WarehouseServiceInterface $warehouseService;
    protected ItemLocationServiceInterface $itemLocationService;
    protected DepartmentServiceInterface $departmentService;

    public function __construct(
        TransactionServiceInterface $transactionService,
        ItemServiceInterface $itemService,
        WarehouseServiceInterface $warehouseService,
        ItemLocationServiceInterface $itemLocationService,
        DepartmentServiceInterface $departmentService
    ) {
        $this->transactionService  = $transactionService;
        $this->itemService         = $itemService;
        $this->warehouseService    = $warehouseService;
        $this->itemLocationService = $itemLocationService;
        $this->departmentService   = $departmentService;
    }

    /* =====================================================================
     |  Daftar transaksi (gabungan jenis yang user BOLEH lihat)
     ===================================================================== */
    public function index(Request $request)
    {
        try {
            $user = auth()->user();
            $seeAll = $user->hasRole('admin') || $user->hasRole('imc');
            // Peta permission "view" → doc_type yang diwakilinya
            $viewMap = [
                'transactions.porc.view' => Transaction::DOC_PORC,
                'transactions.cons.view' => Transaction::DOC_CONS,
                'transactions.adj.view'  => Transaction::DOC_ADJ,
            ];

            $allowedDocTypes = collect($viewMap)
                ->filter(fn($docType, $permission) => auth()->user()->can($permission))
                ->values()
                ->all();

            if (empty($allowedDocTypes)) {
                abort(403);
            }

            if ($request->ajax()) {
                $transactions = $this->transactionService->getAll()
                    ->whereIn('doc_type', $allowedDocTypes);

                // Transaksi milik department lain tidak boleh terlihat.
                if (! $seeAll) {
                    $transactions->where('demander_id', $user->department_id);
                }

                // Kalau user filter jenis tertentu, pastikan jenis itu tetap
                // dalam batas yang dia boleh lihat (cegah bypass lewat query string)
                if ($request->doc_type && in_array($request->doc_type, $allowedDocTypes)) {
                    $transactions->where('doc_type', $request->doc_type);
                }
                if ($request->warehouse_id) {
                    $transactions->where('warehouse_id', $request->warehouse_id);
                }
                if ($request->date_from && $request->date_to) {
                    $transactions->whereBetween('trans_date', [$request->date_from, $request->date_to]);
                }

                return DataTables::of($transactions)
                    ->addIndexColumn()
                    ->addColumn('item', fn($row) => $row->item_desc)
                    ->addColumn('warehouse', fn($row) => $row->warehouse->name . ' - ' . $row->warehouse->tag)
                    ->addColumn('trans_date', fn($row) => Carbon::parse($row->trans_date)->format('d-m-Y'))
                    ->addColumn('doc_type', fn($row) => strtoupper($row->doc_type))
                    ->addColumn('in_qty', fn($row) => (float) $row->in_qty > 0 ? number_format((float) $row->in_qty, 2, ',', '.') : '-')
                    ->addColumn('out_qty', fn($row) => (float) $row->out_qty > 0 ? number_format((float) $row->out_qty, 2, ',', '.') : '-')
                    ->addColumn('created_by', fn($row) => $row->creator->name ?? '-')
                    ->addColumn('action', function ($row) {
                        if ($row->doc_type !== Transaction::DOC_PORC) {
                            return '<span class="text-muted">-</span>';
                        }

                        $btns = '';

                        if (auth()->user()->can('transactions.porc.update')) {
                            $btns .= '<a href="' . route('transactions.porc.edit', $row->id) . '" class="btn btn-sm btn-warning">Edit</a>';
                        }

                        if (auth()->user()->can('transactions.porc.delete')) {
                            $btns .= ' <button type="button" class="btn btn-sm btn-danger btn-delete"
                                    data-name="' . e($row->item_desc) . '"
                                    data-url="' . route('transactions.destroy', $row->id) . '">Hapus</button>';
                        }

                        return $btns ?: '<span class="text-muted">-</span>';
                    })
                    ->rawColumns(['action'])
                    ->make(true);
            }

            $warehouses = $this->warehouseService->getAll()->get();

            return view('pages.transactions.index', compact('warehouses', 'allowedDocTypes'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan transaksi: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data transaksi tidak dapat ditampilkan.');
        }
    }

    /* =====================================================================
     |  PORC — Supply Oil (pemasukan dari vendor)
     ===================================================================== */
    public function createPorc()
    {
        $items       = $this->itemService->getAll()->get();
        $departments = $this->departmentService->getAll()->get();

        // PORC hanya boleh masuk gudang IMC — gudang lain tidak
        // ditampilkan supaya operator tidak salah pilih lalu ditolak
        // saat submit.
        $warehouses = $this->warehouseService->getAll()
            ->whereHas('department', fn($q) => $q->where('code', \App\Models\Department::CODE_IMC))
            ->get();

        return view('pages.transactions.porc', compact('items', 'warehouses', 'departments'));
    }

    public function storePorc(PorcTransactionRequest $request)
    {
        try {
            $this->transactionService->createBatch(
                $request->validated()['entries'],
                auth()->id()
            );

            return redirect()->route('transactions.index')
                ->with('success', 'Supply oil (pemasukan) berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan PORC: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function editPorc(string $id)
    {
        try {
            $transaction = $this->transactionService->getById((int) $id);

            if ($transaction->doc_type !== Transaction::DOC_PORC) {
                throw new \Exception("Hanya transaksi Supply Oil (PORC) yang dapat diedit.");
            }

            // Lot dicari lewat receiving_lot karena nomor itu unik
            // per penerimaan — vendor_lot bisa sama di beberapa PORC.
            $lot = \App\Models\ItemLocation::where('receiving_lot', $transaction->receiving_lot)
                ->where('item_id', $transaction->item_id)
                ->where('warehouse_id', $transaction->warehouse_id)
                ->first();

            if (! $lot) {
                throw new \Exception("Lot dari transaksi ini sudah tidak ditemukan.");
            }

            return view('pages.transactions.porc_edit', compact('transaction', 'lot'));
        } catch (\Exception $e) {
            Log::error('Gagal membuka form edit PORC: ' . $e->getMessage());
            return redirect()->route('transactions.index')->with('error', $e->getMessage());
        }
    }

    public function updatePorc(PorcUpdateRequest $request, string $id)
    {
        try {
            $this->transactionService->updatePorc((int) $id, $request->validated(), auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', 'Transaksi Supply Oil berhasil diperbarui.');
        } catch (\Exception $e) {
            Log::error('Gagal memperbarui PORC: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /* =====================================================================
     |  CONS — Pemakaian (pengeluaran, FEFO)
     ===================================================================== */
    public function createCons()
    {
        $items = $this->itemService->getAll()->get();

        // CONS hanya sah di gudang department — gudang IMC dikecualikan
        // supaya operator tidak salah pilih lalu ditolak guardZone().
        $warehouses = $this->warehouseService->getAll()
            ->whereHas('department', fn($q) => $q->where('code', '!=', \App\Models\Department::CODE_IMC))
            ->when(
                ! auth()->user()->hasRole('admin'),
                fn($q) => $q->where('department_id', auth()->user()->department_id)
            )
            ->get();

        return view('pages.transactions.cons', compact('warehouses', 'items'));
    }

    public function storeCons(ConsTransactionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['doc_type'] = Transaction::DOC_CONS;

            // Pemilik stok = department user yang login. Tidak diambil
            // dari form supaya stok department lain tidak bisa dipakai
            // dengan mengubah nilai input.
            $data['demander_id'] = auth()->user()->department_id;

            if (! $data['demander_id']) {
                throw new \Exception("Akun Anda belum terdaftar di department manapun.");
            }

            $this->transactionService->create($data, auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', 'Pemakaian (pengeluaran) berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan CONS: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /* =====================================================================
     |  ADJ — Adjustment (koreksi lot spesifik)
     ===================================================================== */
    public function createAdj()
    {
        $items      = $this->itemService->getAll()->get();
        $warehouses = $this->warehouseService->getAll()->get();

        return view('pages.transactions.adj', compact('items', 'warehouses'));
    }

    public function storeAdj(AdjTransactionRequest $request)
    {
        try {
            $data = $request->validated();
            $data['doc_type'] = Transaction::DOC_ADJ;

            $this->transactionService->create($data, auth()->id());

            return redirect()->route('transactions.index')
                ->with('success', 'Adjustment berhasil disimpan.');
        } catch (\Exception $e) {
            Log::error('Gagal menyimpan ADJ: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    /* =====================================================================
     |  Hapus (hanya PORC)
     ===================================================================== */
    public function destroy(string $id)
    {
        try {
            $this->transactionService->delete((int) $id);
            return redirect()->route('transactions.index')->with('success', 'Transaksi berhasil dihapus.');
        } catch (\Exception $e) {
            Log::error('Gagal menghapus transaksi: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /* =====================================================================
     |  AJAX helpers
     ===================================================================== */

    // Total stok item di gudang (untuk info di form CONS)
    public function getStock(Request $request)
    {
        $request->validate([
            'item_id'      => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $item = $this->itemService->getById((int) $request->item_id);

        // Difilter demander_id — stok department lain di gudang yang
        // sama tidak boleh ikut terhitung.
        $stock = $this->itemLocationService->getTotalStock(
            (int) $request->item_id,
            (int) $request->warehouse_id,
            auth()->user()->department_id
        );

        return response()->json(['stock' => $stock, 'uom' => $item->item_uom]);
    }

    // Daftar lot di item+gudang (untuk dropdown ADJ)
    public function getLots(Request $request)
    {
        $request->validate([
            'item_id'      => ['required', 'exists:items,id'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
        ]);

        $demanderId = auth()->user()->department_id;

        if (! $demanderId) {
            return response()->json([]);
        }

        $lots = $this->itemLocationService->getAll()
            ->where('item_id', $request->item_id)
            ->where('warehouse_id', $request->warehouse_id)
            // ADJ hanya boleh mengoreksi lot milik department sendiri.
            ->where('demander_id', $demanderId)
            ->get()
            ->map(fn($lot) => [
                'id'    => $lot->id,
                'label' => ($lot->receiving_lot ?? ($lot->vendor_lot ?? 'Lot #' . $lot->id))
                    . ' — Exp: ' . ($lot->exp_date?->format('d/m/Y') ?? '-')
                    . ' — Stok: ' . number_format((float) $lot->qty_weight, 2, ',', '.') . ' kg',
            ]);

        return response()->json($lots);
    }
}
