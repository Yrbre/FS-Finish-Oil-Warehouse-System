<?php

namespace App\Services\Interfaces;

use Carbon\Carbon;

interface StockLedgerServiceInterface
{
    /**
     * Catat 1 baris mutasi, lalu langsung recalculate saldo dari
     * tanggal itu ke depan. Dipanggil setiap ada perubahan stok final.
     */
    public function record(array $data);

    /**
     * Hitung ulang bb_qty/eb_qty secara kronologis mulai tanggal tertentu,
     * lalu sinkronkan hasilnya balik ke tabel transactions.
     *
     * Throw Exception kalau ada titik waktu yang saldonya jadi minus.
     */
    public function recalculateFrom(int $itemId, int $warehouseId, Carbon $fromDate): void;

    /**
     * Hapus jejak ledger dari 1 record asal, lalu recalculate ulang.
     */
    public function removeByRef(string $refType, int $refId, int $itemId, int $warehouseId, Carbon $fromDate): void;

    /**
     * Kartu stok 1 bulan penuh. Semua tanggal terisi, termasuk hari
     * tanpa transaksi (saldonya dibawa dari hari sebelumnya).
     */
    public function getMonthlyStockCard(int $itemId, int $month, int $year, ?int $warehouseId = null);
}
