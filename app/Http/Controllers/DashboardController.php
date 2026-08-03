<?php

namespace App\Http\Controllers;

use App\Models\TransferRequest;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\ItemServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;

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
        $summary = (object) [
            'total_items'      => $this->itemService->getAll()->count(),
            'total_warehouses' => $this->warehouseService->getAll()->count(),
            'total_stock'      => $this->itemLocationService->getGrandTotalStock(),
        ];

        // Widget khusus approver: berapa request menunggu approval
        $pendingApproval = auth()->user()->can('transfer-requests.approve')
            ? $this->transferRequestService->getAll()->where('status', TransferRequest::STATUS_NEW)->count()
            : 0;

        // Widget khusus requester: request milik sendiri yang masih berjalan
        $myOpenRequests = $this->transferRequestService->getAll()
            ->where('requested_by', auth()->id())
            ->whereIn('status', [TransferRequest::STATUS_NEW, TransferRequest::STATUS_IN_TRANSIT])
            ->count();

        $recentTransactions = $this->transactionService->getAll()
            ->latest('trans_date')
            ->latest('id')
            ->take(6)
            ->get();

        $nearExpiry = $this->itemLocationService->getNearExpiring(30, 6);

        return view('dashboard', compact(
            'summary',
            'pendingApproval',
            'myOpenRequests',
            'recentTransactions',
            'nearExpiry'
        ));
    }
}
