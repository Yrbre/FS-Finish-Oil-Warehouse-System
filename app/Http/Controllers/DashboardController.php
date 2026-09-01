<?php

namespace App\Http\Controllers;

use App\Models\TransferRequest;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected ItemServiceInterface $itemService;
    protected WarehouseServiceInterface $warehouseService;
    protected ItemLocationServiceInterface $itemLocationService;
    protected TransactionServiceInterface $transactionService;
    protected TransferRequestServiceInterface $transferRequestService;

    public function __construct(
        ItemServiceInterface $itemService,
        WarehouseServiceInterface $warehouseService,
        ItemLocationServiceInterface $itemLocationService,
        TransactionServiceInterface $transactionService,
        TransferRequestServiceInterface $transferRequestService
    ) {
        $this->itemService            = $itemService;
        $this->warehouseService       = $warehouseService;
        $this->itemLocationService    = $itemLocationService;
        $this->transactionService     = $transactionService;
        $this->transferRequestService = $transferRequestService;
    }

    public function index()
    {
        $user = auth()->user();

        // Admin & IMC memantau seluruh department. Staff hanya
        // melihat yang menjadi miliknya — angka gabungan tidak
        // bermakna baginya, dan bukan haknya.
        $seeAll     = $user->hasRole('admin') || $user->hasRole('imc');
        $demanderId = $seeAll ? null : $user->department_id;

        $summary = (object) [
            'total_items'      => $this->itemService->getAll()->count(),
            'total_warehouses' => $this->warehouseService->getAll()->count(),
            'total_stock'      => $this->itemLocationService->getGrandTotalStock($demanderId),
            'stock_label'      => $seeAll ? 'Total Stok (KG)' : 'Stok Department Anda (KG)',
        ];

        $pendingApproval = $user->can('transfer-requests.approve')
            && $user->isTransferApprover()
            ? $this->transferRequestService->getAll()->where('status', TransferRequest::STATUS_NEW)->count()
            : 0;

        // 'approved' ikut dihitung — request yang sudah disetujui
        // tapi belum dikirim masih berjalan dari sisi pemohon.
        $myOpenRequests = $this->transferRequestService->getAll()
            ->where('requested_by', $user->id)
            ->whereIn('status', [
                TransferRequest::STATUS_NEW,
                TransferRequest::STATUS_APPROVED,
                TransferRequest::STATUS_IN_TRANSIT,
            ])
            ->count();

        $recentTransactions = $this->transactionService->getAll()
            ->when($demanderId, fn($q) => $q->where('demander_id', $demanderId))
            ->latest('trans_date')
            ->latest('id')
            ->take(6)
            ->get();

        $nearExpiry = $this->itemLocationService->getNearExpiring(30, 6, $demanderId);

        return view('dashboard', compact(
            'summary',
            'pendingApproval',
            'myOpenRequests',
            'recentTransactions',
            'nearExpiry'
        ));
    }

    /**
     * AJAX: rekap transfer selesai untuk widget dashboard IMC.
     */
    public function transferSummary(Request $request)
    {
        $request->validate([
            'from' => ['required', 'date'],
            'to'   => ['required', 'date', 'after_or_equal:from'],
        ]);

        return response()->json(
            $this->transferRequestService->getReceivedSummary($request->from, $request->to)
        );
    }
}
