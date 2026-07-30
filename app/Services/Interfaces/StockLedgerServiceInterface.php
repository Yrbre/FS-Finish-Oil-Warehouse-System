<?php

namespace App\Services\Interfaces;

interface StockLedgerServiceInterface
{
    public function record(array $data);

    /**
     * Kartu stok 1 bulan penuh.
     * $warehouseIds = kumpulan gudang yang dijumlah bareng (misal semua
     * gudang milik 1 department). Null = semua gudang tanpa batasan.
     */
    public function getMonthlyStockCard(int $itemId, int $month, int $year, ?array $warehouseIds = null);

    /**
     * Kartu stok versi staff — HANYA Transfer-in (masuk), CONS (keluar),
     * dan ADJ (kolom terpisah). PORC & Transfer-out tidak ikut dihitung.
     */
    public function getStaffMonthlyStockCard(int $itemId, int $month, int $year, array $warehouseIds);
}
