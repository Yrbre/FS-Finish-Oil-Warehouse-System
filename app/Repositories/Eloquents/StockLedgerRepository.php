<?php

namespace App\Repositories\Eloquents;

use App\Models\StockLedger;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use Carbon\Carbon;

class StockLedgerRepository implements StockLedgerRepositoryInterface
{
    protected StockLedger $model;

    public function __construct(StockLedger $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function deleteByRef(string $refType, int $refId): int
    {
        return $this->model
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->delete();
    }

    public function getBalanceBefore(int $itemId, ?array $warehouseIds, Carbon $date): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->where('trans_date', '<', $date->toDateString())
            ->selectRaw('COALESCE(SUM(in_qty) - SUM(out_qty), 0) as balance')
            ->value('balance');
    }

    public function getDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, ?array $warehouseIds = null)
    {
        $inTypes  = "'" . implode("','", StockLedger::inTypes()) . "'";
        $outTypes = "'" . implode("','", StockLedger::outTypes()) . "'";
        $adj      = StockLedger::DOC_ADJ;

        return $this->model
            ->where('item_id', $itemId)
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->whereBetween('trans_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw("
                trans_date,
                SUM(CASE WHEN doc_type IN ({$inTypes}) THEN in_qty ELSE 0 END)   as in_qty,
                SUM(CASE WHEN doc_type IN ({$outTypes}) THEN out_qty ELSE 0 END) as out_qty,
                SUM(CASE WHEN doc_type = '{$adj}' THEN in_qty - out_qty ELSE 0 END) as adj_qty,
                GROUP_CONCAT(DISTINCT doc_type) as doc_types
            ")
            ->groupBy('trans_date')
            ->orderBy('trans_date', 'asc')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->trans_date)->format('Y-m-d'));
    }

    public function getStaffBalanceBefore(int $itemId, array $warehouseIds, Carbon $date): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('doc_type', [StockLedger::DOC_TRANSFER_IN, StockLedger::DOC_CONS, StockLedger::DOC_ADJ])
            ->where('trans_date', '<', $date->toDateString())
            ->selectRaw('COALESCE(SUM(in_qty) - SUM(out_qty), 0) as balance')
            ->value('balance');
    }

    public function getStaffDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, array $warehouseIds)
    {
        $transferIn = StockLedger::DOC_TRANSFER_IN;
        $cons       = StockLedger::DOC_CONS;
        $adj        = StockLedger::DOC_ADJ;

        return $this->model
            ->where('item_id', $itemId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->whereIn('doc_type', [$transferIn, $cons, $adj])
            ->whereBetween('trans_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw("
                trans_date,
                SUM(CASE WHEN doc_type = '{$transferIn}' THEN in_qty ELSE 0 END)  as in_qty,
                SUM(CASE WHEN doc_type = '{$cons}' THEN out_qty ELSE 0 END)       as out_qty,
                SUM(CASE WHEN doc_type = '{$adj}' THEN in_qty - out_qty ELSE 0 END) as adj_qty,
                GROUP_CONCAT(DISTINCT doc_type) as doc_types
            ")
            ->groupBy('trans_date')
            ->orderBy('trans_date', 'asc')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->trans_date)->format('Y-m-d'));
    }

    public function updateByRef(string $refType, int $refId, array $data): int
    {
        return $this->model
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->update($data);
    }
}
