<?php

namespace App\Services\Interfaces;

use App\Models\ItemLocation;
use App\Services\Dto\AllocationResult;
use Illuminate\Support\Collection;

interface ItemLocationServiceInterface
{
    public function getAll();

    public function getById(int $id);

    public function create(array $data);

    public function update(int $id, array $data);

    public function delete(int $id);

    /* ---------------- Stok ---------------- */

    public function getTotalStock(int $itemId, int $warehouseId, ?int $demanderId = null): float;

    public function getTotalStockByDepartment(?int $itemId, int $departmentId): float;

    public function getTotalStockAllWarehouses(int $itemId): float;

    public function getTotalStockByDemander(int $itemId, int $demanderId, ?array $warehouseIds = null): float;

    /* ---------------- Alokasi ---------------- */

    /** TRANSFER: package utuh, ukuran cocok persis. */
    public function allocateForTransfer(
        int $itemId,
        int $demanderId,
        array $warehouseIds,
        float $perPackage,
        float $packageNeeded
    ): AllocationResult;

    /** CONS: potong kg, lintas ukuran kemasan. */
    public function allocateForCons(
        int $itemId,
        int $warehouseId,
        int $demanderId,
        float $weightNeeded
    ): AllocationResult;

    public function getAvailablePackageSizes(int $itemId, int $demanderId, array $warehouseIds): Collection;

    /* ---------------- Mutasi lot ---------------- */

    public function deductLot(int $itemLocationId, float $qtyWeight): ItemLocation;

    public function addLot(array $lotData): ItemLocation;

    public function generateReceivingLot($receivingDate);

    /* ---------------- Report ---------------- */

    public function getGrandTotalStock(): float;

    public function getNearExpiring(int $days = 30, int $limit = 10);

    public function getStockSummaryByWarehouse();

    /** Semua lot yang bisa dipakai transfer, untuk form alokasi manual. */
    public function getTransferLots(int $itemId, int $demanderId, array $warehouseIds, float $perPackage): Collection;
}
