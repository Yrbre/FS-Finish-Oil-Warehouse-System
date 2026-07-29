<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;

interface StockLedgerRepositoryInterface
{
    public function create(array $data);

    public function deleteByRef(string $refType, int $refId): int;

    /**
     * Saldo akumulasi sebelum tanggal tertentu.
     * $warehouseIds = array of warehouse_id yang dijumlah bareng
     * (misal semua gudang milik 1 department). Null = semua warehouse.
     */
    public function getBalanceBefore(int $itemId, ?array $warehouseIds, Carbon $date): float;

    /**
     * Mutasi harian yang sudah diagregasi di level SQL, untuk laporan.
     * $warehouseIds sama seperti di atas.
     */
    public function getDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, ?array $warehouseIds = null);
}
