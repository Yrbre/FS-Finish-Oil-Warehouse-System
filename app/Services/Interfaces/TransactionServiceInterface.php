<?php

namespace App\Services\Interfaces;

interface TransactionServiceInterface
{
    public function getAll();
    public function getById(int $id);

    /**
     * Buat transaksi PORC / CONS / ADJ.
     *
     * Alurnya:
     *   1. Tentukan arah mutasi (in/out) dari doc_type
     *   2. Simpan record transaksi
     *   3. Terapkan perubahan stok fisik di item_locations
     *   4. Catat ke stock_ledger + recalculate saldo
     *
     * Semua dibungkus 1 DB transaction. Kalau ada saldo yang jadi minus
     * di titik waktu manapun, seluruh proses dibatalkan.
     */
    public function create(array $data, int $createdBy);

    public function createBatch(array $entries, int $createdBy): array;

    public function updatePorc(int $id, array $data, int $editedBy);

    /**
     * Hapus transaksi beserta jejaknya di ledger, lalu recalculate.
     * Hanya PORC yang bisa dihapus.
     */
    public function delete(int $id);
}
