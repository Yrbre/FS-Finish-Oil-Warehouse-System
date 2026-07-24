<?php

namespace App\Repositories\Interfaces;

use Carbon\Carbon;

interface StockLedgerRepositoryInterface
{
    public function create(array $data);

    /**
     * Saldo stok sebelum tanggal tertentu (opening balance).
     * Dihitung dari akumulasi in - out, bukan baca kolom eb_qty,
     * supaya tetap akurat walaupun urutan input tidak kronologis.
     */
    public function getBalanceBefore(int $itemId, int $warehouseId, Carbon $date): float;

    /**
     * Saldo stok sebelum tanggal tertentu, gabungan semua warehouse.
     * Dipakai untuk laporan kartu stok lintas gudang.
     */
    public function getBalanceBeforeAllWarehouses(int $itemId, Carbon $date): float;

    /**
     * Semua baris ledger dari tanggal tertentu ke depan, urut kronologis.
     * Dipakai saat recalculate akibat transaksi backdate.
     */
    public function getFromDate(int $itemId, int $warehouseId, Carbon $fromDate);

    /**
     * Update saldo 1 baris ledger.
     */
    public function updateBalance(int $id, float $bbQty, float $ebQty): void;

    /**
     * Hapus baris ledger berdasarkan record aslinya.
     */
    public function deleteByRef(string $refType, int $refId): int;

    /**
     * Mutasi harian yang sudah diagregasi di level SQL.
     * Return: collection dengan key tanggal (Y-m-d).
     */
    public function getDailyMutation(int $itemId, Carbon $startDate, Carbon $endDate, ?int $warehouseId = null);
}
