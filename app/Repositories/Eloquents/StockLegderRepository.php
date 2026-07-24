<?php

namespace App\Repositories\Eloquents;

use App\Models\StockLedger;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use Carbon\Carbon;

class StockLedgerRepository implements StockLedgerRepositoryInterface
{
    protected StockLedger $model;
    public function __construct(StockLedger $stockLedger)
    {
        $this->model = $stockLedger;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function getBalanceBefore(int $itemId, int $warehouseId, Carbon $date): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('trans_date', '<', $date->toDateString())
            ->selectRaw('COALESCE(SUM(in_qty) - SUM(out_qty), 0) as balance')
            ->value('balance');
    }

    public function getBalanceBeforeAllWarehouses(int $itemId, Carbon $date): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->where('trans_date', '<', $date->toDateString())
            ->selectRaw('COALESCE(SUM(in_qty) - SUM(out_qty), 0) as balance')
            ->value('balance');
    }

    public function getFromDate(int $itemId, int $warehouseId, Carbon $fromDate)
    {
        return $this->model
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('trans_date', '>=', $fromDate->toDateString())
            ->orderBy('trans_date', 'asc')
            ->orderBy('id', 'asc') // urutan input untuk transaksi di hari yang sama
            ->get();
    }

    public function updateBalance(int $id, float $inQty, float $outQty): void
    {
        $this->model->where('id', $id)->update([
            'in_qty' => $inQty,
            'out_qty' => $outQty,
        ]);
    }

    public function deleteByRef(string $refType, int $refId): int
    {
        return $this->model
            ->where('ref_type', $refType)
            ->where('ref_id', $refId)
            ->delete();
    }

    public function getDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, ?int $warehouseId = null)
    {
        $inTypes = "'" . implode("','", StockLedger::inTypes()) . "'";
        $outTypes = "'" . implode("','", StockLedger::outTypes()) . "'";
        $adj = StockLedger::DOC_ADJ;

        return $this->model
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($q) => $q->where('warehouse_id', $warehouseId))
            ->whereBetween('trans_date', [$startDate->toDateString(), $endDate->toDateString()])
            ->selectRaw("
        trans_date,
        SUM(CASE WHEN doc_type IN ({$inTypes}) THEN in_qty ELSE 0 END) as in_qty,
        SUM(CASE WHEN doc_type IN ({$outTypes}) THEN out_qty ELSE 0 END) as out_qty,
        SUM(CASE WHEN doc_type = '{$adj}' THEN in_qty ELSE 0 END) as adj_in_qty,
        GROUP_CONCAT(DISTINCT doc_type) as doc_types
        ")
            ->groupBy('trans_date')
            ->orderBy('trans_date', 'asc')
            ->get()
            ->keyBy(fn($row) => Carbon::parse($row->trans_date)->format('Y-m-d'));
    }
}
