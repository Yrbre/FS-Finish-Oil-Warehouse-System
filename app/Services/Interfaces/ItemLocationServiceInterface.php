<?php

namespace App\Services\Interfaces;

interface ItemLocationServiceInterface
{
    public function getAll();
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    public function getTotalStock(int $itemId, int $warehouseId): float;
    public function getTotalStockAllWarehouses(int $itemId): float;

    /**
     * Alokasi FEFO dalam 1 warehouse. Throw Exception kalau stok kurang.
     *
     * Return: array of [
     *   'item_location' => ItemLocation,
     *   'qty_to_take'   => float,
     * ]
     */
    public function allocateFefo(int $itemId, int $warehouseId, float $qtyNeeded): array;

    /**
     * Alokasi FEFO lintas seluruh warehouse (untuk Transfer).
     * TIDAK throw exception kalau kurang — sisa kebutuhan dikembalikan
     * lewat parameter referensi $remainingQty, supaya pemanggil bisa
     * menampilkan rekomendasi parsial ke user.
     */
    public function allocateFefoAcrossWarehouses(
        int $itemId,
        float $qtyNeeded,
        array $warehouseIds,
        float &$remainingQty
    ): array;

    /**
     * Kurangi stok sejumlah tertentu dari 1 lot.
     */
    public function deductLot(int $itemLocationId, float $qty);

    /**
     * Tambah stok ke gudang tujuan. Kalau lot dengan vendor_lot &
     * exp_date yang sama sudah ada, qty ditambahkan ke lot itu.
     * Kalau belum ada, dibuat record baru.
     */
    public function addOrMergeLot(int $itemId, int $warehouseId, array $lotData);

    // Report
    public function getGrandTotalStock(): float;
    public function getNearExpiring(int $days = 30, int $limit = 10);
    public function getStockSummaryByWarehouse();
}
