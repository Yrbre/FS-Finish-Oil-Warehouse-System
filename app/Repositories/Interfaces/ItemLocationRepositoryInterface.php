<?php

namespace App\Repositories\Interfaces;

use Illuminate\Support\Collection;

interface ItemLocationRepositoryInterface
{
    public function getAll();

    public function getById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    /* ---------------- Stok ---------------- */

    /**
     * Total stok. $demanderId wajib diisi kalau ingin stok milik
     * department tertentu — tanpa itu, hasilnya stok seluruh
     * department yang ada di gudang tersebut.
     */
    public function getTotalStock(int $itemId, int $warehouseId, ?int $demanderId = null): float;

    public function getTotalStockByDepartment(?int $itemId, int $departmentId): float;

    public function getTotalStockAllWarehouses(int $itemId): float;

    /**
     * Total stok milik satu department, lintas gudang.
     * Dipakai untuk pengecekan min-stock.
     */
    public function getTotalStockByDemander(int $itemId, int $demanderId, ?array $warehouseIds = null): float;

    /* ---------------- FEFO ---------------- */

    /**
     * Lot untuk TRANSFER: di gudang IMC, milik department pemohon,
     * ukuran kemasan cocok persis, terurut FEFO.
     */
    public function getFefoLotsForTransfer(
        int $itemId,
        int $demanderId,
        array $warehouseIds,
        float $perPackage
    ): Collection;

    /**
     * Lot untuk CONS: di gudang staff, milik department pemakai,
     * semua ukuran kemasan, terurut FEFO.
     */
    public function getFefoLotsForCons(int $itemId, int $warehouseId, int $demanderId): Collection;

    /**
     * Daftar ukuran kemasan yang tersedia — untuk dropdown
     * di form transfer request.
     */
    public function getAvailablePackageSizes(int $itemId, int $demanderId, array $warehouseIds): Collection;

    /* ---------------- Report ---------------- */

    public function getGrandTotalStock(): float;

    public function getNearExpiring(int $days = 30, int $limit = 10);

    public function getStockSummaryByWarehouse();
}
