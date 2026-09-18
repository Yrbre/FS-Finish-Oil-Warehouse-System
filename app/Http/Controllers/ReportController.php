<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\ItemLocation;
use App\Services\Interfaces\ItemLocationServiceInterface;

class ReportController extends Controller
{
    public function __construct(
        protected ItemLocationServiceInterface $itemLocationService,
    ) {}

    public function index()
    {
        $itemSF = ItemLocation::with('item')
            ->whereHas('warehouse', function ($query) {
                $idIMC = Department::where('code', 'IMC')->first()->id;
                $query->where('department_id', $idIMC);
            })
            ->whereHas('demander', function ($query) {
                $query->where('code', 'SF');
            })
            ->where('is_warehouse_stock', true)
            ->where('qty_weight', '>', 0)
            ->get();

        $itemFY = ItemLocation::with('item')
            ->whereHas('warehouse', function ($query) {
                $idIMC = Department::where('code', 'IMC')->first()->id;
                $query->where('department_id', $idIMC);
            })
            ->whereHas('demander', function ($query) {
                $query->whereIn('code', ['FY1', 'FY2', 'FY3']);
            })
            ->where('is_warehouse_stock', true)
            ->where('qty_weight', '>', 0)
            ->get();

        $itemHSF = ItemLocation::with('item')
            ->whereHas('warehouse', function ($query) {
                $idIMC = Department::where('code', 'IMC')->first()->id;
                $query->where('department_id', $idIMC);
            })
            ->whereHas('demander', function ($query) {
                $query->whereIn('code', ['PBX', 'PCP']);
            })
            ->where('is_warehouse_stock', true)
            ->where('qty_weight', '>', 0)
            ->get();

        return view('pages.reports.index', compact('itemSF', 'itemFY', 'itemHSF'));
    }
}
