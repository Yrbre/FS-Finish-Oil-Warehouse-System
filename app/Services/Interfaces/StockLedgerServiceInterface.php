<?php

namespace App\Services\Interfaces;

interface StockLedgerServiceInterface
{
    /**
     * Catat 1 baris arsip riwayat. Murni append — tidak menghitung ulang
     * apapun. bb_qty/eb_qty sudah final dan dikirim oleh pemanggil
     * (TransactionService, TransferRequestService), yang menghitungnya
     * langsung dari kondisi item_locations saat itu.
     */
    public function record(array $data);

    /**
     * Kartu stok 1 bulan penuh, untuk laporan.
     * Semua tanggal terisi, saldo hari kosong dibawa dari hari sebelumnya.
     * Ini murni MEMBACA arsip — tidak pernah menulis ulang.
     */
    public function getMonthlyStockCard(int $itemId, int $month, int $year, ?int $warehouseId = null);
}
