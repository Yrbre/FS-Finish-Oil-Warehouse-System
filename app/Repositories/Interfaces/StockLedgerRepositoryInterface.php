<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;

interface StockLedgerRepositoryInterface
{
    public function create(array $data);

    /**
     * Hapus baris arsip berdasarkan referensi record aslinya.
     * Dipakai saat transaksi PORC dihapus.
     */
    public function deleteByRef(string $refType, int $refId): int;

    /**
     * Saldo akumulasi sebelum tanggal tertentu, 1 warehouse.
     * Murni untuk laporan (kartu stok), bukan untuk validasi transaksi baru.
     */
    public function getBalanceBefore(int $itemId, int $warehouseId, Carbon $date): float;

    /**
     * Sama seperti di atas, tapi gabungan semua warehouse.
     */
    public function getBalanceBeforeAllWarehouses(int $itemId, Carbon $date): float;

    /**
     * Mutasi harian yang sudah diagregasi di level SQL, untuk laporan.
     */
    public function getDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, ?int $warehouseId = null);
}
