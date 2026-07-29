<?php

namespace App\Http\Controllers;

use App\Services\Interfaces\ItemLocationServiceInterface;

class ReportController extends Controller
{
    protected ItemLocationServiceInterface $itemLocationService;

    public function __construct(ItemLocationServiceInterface $itemLocationService)
    {
        $this->itemLocationService = $itemLocationService;
    }

    public function index()
    {
        $nearExpiry    = $this->itemLocationService->getNearExpiring(30, 50);
        $stockByWarehouse = $this->itemLocationService->getStockSummaryByWarehouse();

        return view('pages.reports.index', compact('nearExpiry', 'stockByWarehouse'));
    }
}
