<?php

namespace App\Repositories\Interfaces;

interface ItemLocationsRepositoryInterface
{
    public function getAll();
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    /**
     * Total stok (qty_weight) 1 item di 1 warehouse.
     */
    public function getTotalStock(int $itemId, int $warehouseId): float;

    /**
     * Total stok 1 item di seluruh warehouse.
     */
    public function getTotalStockAllWarehouses(int $itemId): float;

    /**
     * Lot yang masih ada stoknya di 1 warehouse, urut FEFO.
     * Dipakai untuk transaksi CONS.
     */
    public function getFefoLots(int $itemId, int $warehouseId);

    /**
     * Lot yang masih ada stoknya di SEMUA warehouse, urut FEFO.
     * Dipakai untuk rekomendasi Transfer (FEFO lintas warehouse).
     */
    public function getFefoLotsAcrossWarehouses(int $itemId, ?int $excludeWarehouseId = null);

    /**
     * Cari lot dengan vendor_lot & exp_date yang sama di warehouse tertentu.
     * Dipakai saat barang transfer diterima — kalau lot sudah ada,
     * qty ditambahkan; kalau belum, buat record baru.
     */
    public function findMatchingLot(int $itemId, int $warehouseId, ?string $vendorLot, ?string $expDate);
}
