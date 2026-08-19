<?php

namespace App\Http\Controllers;

use App\Http\Requests\RejectTransferRequestRequest;
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
                if (! auth()->user()->can('transfer-requests.approve')) {
                    $transferRequests->where('requested_by', auth()->id());
                }

                return DataTables::of($transferRequests)
                    ->addIndexColumn()
                    ->addColumn('checkbox',  fn($row) => '<input type="checkbox" class="row-checkbox" value="' . $row->id . '">')
                    ->addColumn('item', fn($row) => $row->item->item_desc)
                    ->addColumn('destination', fn($row) => $row->destinationWarehouse->name)
                    ->addColumn('requested_qty', fn($row) => number_format((float) $row->requested_qty, 2, ',', '.'))
                    ->addColumn('expected_date', fn($row) => Carbon::parse($row->expected_date)->format('d-m-Y'))
                    ->addColumn('requester', fn($row) => $row->requester->name)
                    ->addColumn('status', fn($row) => $this->statusBadge($row->status))
                    ->addColumn('action', function ($row) {
                        $urlCetakSatuan = route('transfer-requests.cetak-batch', ['ids' => [$row->id]]);

                        $buttons = '<a href="' . route('transfer-requests.show', $row->id) . '" class="btn btn-sm btn-info">Detail</a>' .
                            ' <a href="' . $urlCetakSatuan . '" target="_blank" class="btn btn-sm btn-warning">Cetak</a>';
                        return $buttons;
                    })
                    ->rawColumns(['status', 'action', 'checkbox'])
                    ->make(true);
            }

            return view('pages.transfer_requests.index');
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan Permintaan Kirim Barang : ' . $e->getMessage());
            return redirect()->back()->with('error', 'Data Permintaan Kirim Barang  tidak dapat ditampilkan.');
        }
    }

    public function cetakBatch(Request $request)
    {
        $request->validate([
            'ids'   => 'required|array|min:1',
            'ids.*' => 'exists:transfer_requests,id',
        ]);

        $transferRequests = TransferRequest::with([
            'details.itemLocation.item',
            'destinationWarehouse',
            'requester',
        ])->whereIn('id', $request->ids)->get();

        if ($transferRequests->isEmpty()) {
            return redirect()->back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $pdf = Pdf::loadView('pdf.ttb-batch', compact('transferRequests'))
            ->setPaper('letter', 'portrait');

        return $pdf->stream('ttb-batch-' . now()->format('YmdHis') . '.pdf');
    }

    public function create()
    {
        $items      = $this->itemService->getAll()->get();
        if (auth()->user()->hasRole('admin')) {
            $warehouses = $this->warehouseService->getAll()->get();
        } else {
            $warehouses = $this->warehouseService->getByDepartment(auth()->user()->department_id);
        }


        return view('pages.transfer_requests.create', compact('items', 'warehouses'));
    }

    public function store(TransferRequestStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $data['department_id'] = auth()->user()->department_id;

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

            $recommendation = null;
            $availableLots  = null;

            if ($transferRequest->status === TransferRequest::STATUS_NEW) {
                // Tetap dihitung untuk info ringkas (total & status cukup/tidak)
                $recommendation = $this->transferRequestService->getRecommendation((int) $id);

                // Daftar LENGKAP lot yang bisa dipilih manual, dengan saran FEFO
                $availableLots = $this->transferRequestService->getAvailableLots((int) $id);
            }

            return view('pages.transfer_requests.show', compact('transferRequest', 'recommendation', 'availableLots'));
        } catch (\Exception $e) {
            Log::error('Gagal menampilkan detail Permintaan Kirim Barang : ' . $e->getMessage());
            return redirect()->route('transfer-requests.index')->with('error', 'Permintaan Kirim Barang tidak ditemukan.');
        }
    }

    public function approve(Request $request, string $id)
    {
        try {
            // allocation[] dikirim dari form: item_location_id => qty.
            // Kalau approver tidak ubah apapun, nilainya = saran FEFO
            // (sudah di-pre-fill di view) — jadi hasilnya sama saja
            // seperti auto-approve, tapi tetap lewat jalur validasi manual.
            $manualAllocation = $request->filled('allocation')
                ? collect($request->input('allocation'))
                ->map(fn($qty) => (float) $qty)
                ->filter(fn($qty) => $qty > 0)
                ->toArray()
                : null;

            $transferRequest = $this->transferRequestService->approve(
                (int) $id,
                auth()->id(),
                $request->input('effective_date'),
                $manualAllocation
            );

            return redirect()->route('transfer-requests.show', $id)
                ->with('success', 'Permintaan Kirim Barang disetujui, stok telah dikirim (' . $transferRequest->transfer_code . ').');
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

    public function reject(RejectTransferRequestRequest $request, string $id)
    {
        try {
            $this->transferRequestService->reject(
                (int) $id,
                auth()->id(),
                $request->validated()['reject_reason']
            );

            return redirect()->route('transfer-requests.index')->with('success', 'Permintaan Kirim Barang ditolak.');
        } catch (\Exception $e) {
            Log::error('Gagal reject Permintaan Kirim Barang: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    public function cancel(string $id)
    {
        try {
            $this->transferRequestService->cancel((int) $id, auth()->id());

            return redirect()->route('transfer-requests.index')->with('success', 'Permintaan Kirim Barang dibatalkan.');
        } catch (\Exception $e) {
            Log::error('Gagal cancel Permintaan Kirim Barang: ' . $e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    private function statusBadge(string $status): string
    {
        $map = [
            TransferRequest::STATUS_NEW        => 'badge-secondary',
            TransferRequest::STATUS_IN_TRANSIT => 'badge-primary',
            TransferRequest::STATUS_RECEIVED   => 'badge-success',
            TransferRequest::STATUS_REJECTED   => 'badge-danger',
            TransferRequest::STATUS_CANCELLED  => 'badge-light',
        ];

        $class = $map[$status] ?? 'badge-secondary';

        return '<span class="badge ' . $class . '">' . strtoupper($status) . '</span>';
    }
}
