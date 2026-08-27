<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReceiptBatchRequest;
use App\Http\Requests\ReceiptOfGoodsRequest;
use App\Http\Requests\RejectTransferItemRequest;
use App\Http\Requests\TransferRequestStoreRequest;
use App\Models\TransferRequest;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Yajra\DataTables\Facades\DataTables;

class TransferRequestController extends Controller
{
    protected TransferRequestServiceInterface $transferRequestService;
    protected ItemServiceInterface $itemService;
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(
        TransferRequestServiceInterface $transferRequestService,
        ItemServiceInterface $itemService,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->transferRequestService = $transferRequestService;
        $this->itemService            = $itemService;
        $this->warehouseService       = $warehouseService;
    }

    public function index(Request $request)
    {
        try {
            if ($request->ajax()) {
                $transferRequests = $this->transferRequestService->getAll();

                if ($request->status) {
                    $transferRequests->where('status', $request->status);
                }

                // Non-approver hanya lihat request miliknya sendiri
                if (! auth()->user()->hasRole('admin') && ! auth()->user()->can('transfer-requests.approve')) {
                    $transferRequests->where('department_id', auth()->user()->department_id);
                }

                return DataTables::of($transferRequests)
                    ->addIndexColumn()
                    ->addColumn('checkbox', fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                    ->addColumn('item_summary', function ($row) {
                        $count = $row->items_count;
                        $first = $row->items->first()?->item->item_desc ?? '-';

                        return $count > 1
                            ? e($first) . ' <span class="badge badge-light">+' . ($count - 1) . ' item</span>'
                            : e($first);
                    })
                    ->addColumn('destination', fn($row) => $row->destinationWarehouse->name . ' - ' . $row->destinationWarehouse->tag)
                    ->addColumn('expected_date', fn($row) => Carbon::parse($row->expected_date)->format('d-m-Y'))
                    ->addColumn('requester', fn($row) => $row->requester->name)
                    ->addColumn('status', fn($row) => $this->statusBadge($row->status))
                    ->addColumn('action', fn($row) => '<a href="' . route('transfer-requests.show', $row->id) . '" class="btn btn-sm btn-info">Detail</a>')
                    ->rawColumns(['status', 'action', 'checkbox', 'item_summary'])
                    ->make(true);
            }

            return view('pages.transfer_requests.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan Permintaan Kirim Barang : ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data Permintaan Kirim Barang  tidak dapat ditampilkan.');
        }
    }

    public function cetakBatch(ReceiptBatchRequest $request)
    {
        try {
            $transferRequests = $this->transferRequestService->issueReceiptBatch(
                $request->validated()['ids'],
                auth()->id(),
                $request->validated()['letter_date']
            );

            $pdf = Pdf::loadView('pdf.receipt-of-goods', compact('transferRequests'))
                ->setPaper('letter', 'portrait');

            return $pdf->stream('tanda-terima-' . now()->format('YmdHis') . '.pdf');
        } catch (\Exception $e) {
            Log::error('Gagal cetak tanda terima batch: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }



    public function create()
    {
        $items      = $this->itemService->getAll()->get();

        $warehouses = auth()->user()->hasRole('admin')
            ? $this->warehouseService->getAll()->get()
            : $this->warehouseService->getByDepartment(auth()->user()->department_id);


        return view('pages.transfer_requests.create', compact('items', 'warehouses'));
    }

    public function store(TransferRequestStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $data['department_id'] = auth()->user()->department_id;

            if (! $data['department_id']) {
                throw new \Exception("Akun Anda belum terdaftar di department manapun.");
            }

            $transferRequest = $this->transferRequestService->create($data, auth()->id());

            return redirect()->route('transfer-requests.show', $transferRequest->id)
                ->with('success', 'Permintaan Kirim Barang berhasil dibuat: ' . $transferRequest->transfer_code);
        } catch (\Exception $e) {
            Log::error('Gagal membuat Permintaan Kirim Barang: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage())->withInput();
        }
    }

    public function show(string $id)
    {
        try {
            $transferRequest = $this->transferRequestService->getById((int) $id);
            $this->guardCanView($transferRequest);

            $recommendation = null;

            if ($transferRequest->status === TransferRequest::STATUS_NEW) {
                $recommendation = $this->transferRequestService->getRecommendation((int) $id);
            }

            return view('pages.transfer_requests.show', compact('transferRequest', 'recommendation'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan detail Permintaan Kirim Barang : ' . $e->getMessage());
            return redirect()->route('transfer-requests.index')->with('error', 'Permintaan Kirim Barang tidak ditemukan.');
        }
    }

    public function approve(Request $request, string $id)
    {
        try {
            // allocation[transfer_request_item_id][item_location_id] = package
            $manualAllocation = null;

            if ($request->filled('allocation')) {
                $manualAllocation = collect($request->input('allocation'))
                    ->map(fn($lots) => collect($lots)
                        ->map(fn($pkg) => (float) $pkg)
                        ->filter(fn($pkg) => $pkg > 0)
                        ->toArray())
                    ->filter(fn($lots) => ! empty($lots))
                    ->toArray();
            }

            $transferRequest = $this->transferRequestService->approve(
                (int) $id,
                auth()->id(),
                $request->input('effective_date'),
                $manualAllocation
            );

            return redirect()->route('transfer-requests.show', $id)
                ->with('success', 'Permintaan disetujui (' . $transferRequest->transfer_code . ').');
        } catch (\Exception $e) {
            Log::error('Gagal approve Permintaan Kirim Barang: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function receive(Request $request, string $id)
    {
        try {
            $this->transferRequestService->receive(
                (int) $id,
                auth()->id(),
                $request->input('effective_date')
            );

            return redirect()->route('transfer-requests.show', $id)
                ->with('success', 'Barang berhasil dikonfirmasi diterima.');
        } catch (\Exception $e) {
            Log::error('Gagal konfirmasi terima Permintaan Kirim Barang : ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function rejectItem(RejectTransferItemRequest $request, string $itemId)
    {
        try {
            $trItem = $this->transferRequestService->rejectItem(
                (int) $itemId,
                auth()->id(),
                $request->validated()['reason']
            );

            return redirect()->route('transfer-requests.show', $trItem->transfer_request_id)
                ->with('success', 'Item berhasil ditolak.');
        } catch (\Exception $e) {
            Log::error('Gagal menolak item: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancelItem(string $itemId)
    {
        try {
            $trItem = $this->transferRequestService->cancelItem((int) $itemId, auth()->id());

            return redirect()->route('transfer-requests.show', $trItem->transfer_request_id)
                ->with('success', 'Item berhasil dibatalkan.');
        } catch (\Exception $e) {
            Log::error('Gagal membatalkan item: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /** Batalkan item yang sudah approved — stok dikembalikan ke lot asal. */
    public function cancelApprovedItem(RejectTransferItemRequest $request, string $itemId)
    {
        try {
            $trItem = $this->transferRequestService->cancelApprovedItem(
                (int) $itemId,
                auth()->id(),
                $request->validated()['reason']
            );

            return redirect()->route('transfer-requests.show', $trItem->transfer_request_id)
                ->with('success', 'Item dibatalkan, stok telah dikembalikan ke gudang asal.');
        } catch (\Exception $e) {
            Log::error('Gagal membatalkan item approved: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function statusBadge(string $status): string
    {
        $map = [
            TransferRequest::STATUS_NEW        => 'badge-secondary',
            TransferRequest::STATUS_APPROVED   => 'badge-info',
            TransferRequest::STATUS_IN_TRANSIT => 'badge-primary',
            TransferRequest::STATUS_RECEIVED   => 'badge-success',
            TransferRequest::STATUS_REJECTED   => 'badge-danger',
            TransferRequest::STATUS_CANCELLED  => 'badge-light',
        ];

        $class = $map[$status] ?? 'badge-secondary';

        return '<span class="badge ' . $class . '">' . strtoupper($status) . '</span>';
    }

    /**
     * AJAX: ukuran kemasan yang tersedia di IMC untuk department user.
     * Dipakai dropdown di form request supaya staff tidak bisa
     * meminta ukuran yang tidak ada atau melebihi stok.
     */
    public function getPackageSizes(Request $request)
    {
        $request->validate(['item_id' => ['required', 'exists:items,id']]);

        $demanderId = auth()->user()->department_id;

        if (! $demanderId) {
            return response()->json([]);
        }

        $sizes = $this->transferRequestService->getAvailablePackageSizes(
            (int) $request->item_id,
            (int) $demanderId
        );

        return response()->json($sizes);
    }

    public function issueReceipt(ReceiptOfGoodsRequest $request, string $id)
    {
        try {
            $data = $request->validated();

            if ($request->hasFile('photo')) {
                $data['photo'] = $request->file('photo')->store('receipts', 'public');
            }

            $this->transferRequestService->issueReceipt((int) $id, auth()->id(), $data);

            // Langsung kembalikan PDF — form-nya target="_blank", jadi
            // dokumen terbuka di tab baru sementara tab asal di-refresh
            // oleh JS untuk menampilkan status terbaru.
            $transferRequest = $this->transferRequestService->getById((int) $id);
            $transferRequests = collect([$transferRequest]);

            $pdf = Pdf::loadView('pdf.receipt-of-goods', compact('transferRequests'))
                ->setPaper('letter', 'portrait');

            return $pdf->stream('tanda-terima-' . $transferRequest->transfer_code . '.pdf');
        } catch (\Exception $e) {
            Log::error('Gagal membuat tanda terima: ' . $e->getMessage());

            // Error tampil di tab baru karena form target="_blank".
            return response()->view('pages.transfer_requests.receipt_error', [
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Cetak tanda terima. Cetak pertama sudah dilakukan issueReceipt();
     * di sini hanya menaikkan print_count sebagai jejak berapa kali
     * dokumen dicetak ulang.
     */
    public function printReceipt(string $id)
    {
        try {
            $transferRequest = $this->transferRequestService->getById((int) $id);

            $this->guardCanView($transferRequest);

            if (! $transferRequest->receiptOfGoods) {
                throw new \Exception("Tanda terima untuk request ini belum dibuat.");
            }

            $this->transferRequestService->markPrinted([$transferRequest->id]);

            // Template menerima koleksi, jadi cetak satuan dan batch
            // memakai layout yang persis sama.
            $transferRequests = collect([$transferRequest]);

            $pdf = Pdf::loadView('pdf.receipt-of-goods', compact('transferRequests'))
                ->setPaper('letter', 'portrait');

            return $pdf->stream('tanda-terima-' . $transferRequest->transfer_code . '.pdf');
        } catch (\Exception $e) {
            Log::error('Gagal mencetak tanda terima: ' . $e->getMessage());

            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function guardCanView(TransferRequest $transferRequest): void
    {
        $user = auth()->user();

        // Petugas penerbit tanda terima perlu akses ke semua request
        // untuk mencetak dokumennya, meski bukan pemohon maupun approver.
        if (
            $user->hasRole('admin')
            || $user->can('transfer-requests.approve')
            || $user->canIssueReceipt()
        ) {
            return;
        }

        $milikSendiri   = (int) $transferRequest->requested_by === (int) $user->id;
        $departmentSama = (int) $transferRequest->department_id === (int) $user->department_id;

        if (! $milikSendiri && ! $departmentSama) {
            throw new \Exception("Anda tidak memiliki akses ke Permintaan Kirim Barang ini.");
        }
    }
}
