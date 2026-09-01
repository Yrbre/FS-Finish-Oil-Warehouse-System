<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\ItemLocationServiceInterface;

class ReportController extends Controller
{
    public function __construct(
        protected ItemLocationServiceInterface $itemLocationService,
    ) {}

    public function index()
    {
        $user = auth()->user();

        // Laporan staff dibatasi stok miliknya sendiri.
        $seeAll     = $user->hasRole('admin') || $user->hasRole('imc');
        $demanderId = $seeAll ? null : $user->department_id;

        $nearExpiry       = $this->itemLocationService->getNearExpiring(30, 50, $demanderId);
        $stockByWarehouse = $this->itemLocationService->getStockSummaryByWarehouse($demanderId);

        return view('pages.reports.index', compact('nearExpiry', 'stockByWarehouse', 'seeAll'));
    }
}
