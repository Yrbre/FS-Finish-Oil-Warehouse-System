<?php

namespace App\Services;

use App\Models\ItemLocation;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Services\Dto\AllocationResult;
use App\Services\Interfaces\ItemLocationServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ItemLocationService implements ItemLocationServiceInterface
{
    public function __construct(
        protected ItemLocationRepositoryInterface $itemLocationRepository,
        protected PackageAllocator $allocator,
    ) {}

    public function getAll()
    {
        return $this->itemLocationRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->itemLocationRepository->getById($id);
    }

    public function create(array $data)
    {
        $data = $this->prepareLotData($data);

        return $this->itemLocationRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        // Sengaja TIDAK memanggil prepareLotData: kalau exp_date
        // dihitung ulang setiap update, tanggal yang sudah dikoreksi
        // manual akan tertimpa diam-diam.
        return $this->itemLocationRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->itemLocationRepository->delete($id);
    }

    /* ================= STOK ================= */

    public function getTotalStock(int $itemId, int $warehouseId, ?int $demanderId = null): float
    {
        return $this->itemLocationRepository->getTotalStock($itemId, $warehouseId, $demanderId);
    }

    public function getTotalStockByDepartment(?int $itemId, int $departmentId): float
    {
        return $this->itemLocationRepository->getTotalStockByDepartment($itemId, $departmentId);
    }

    public function getTotalStockAllWarehouses(int $itemId): float
    {
        return $this->itemLocationRepository->getTotalStockAllWarehouses($itemId);
    }

    public function getTotalStockByDemander(int $itemId, int $demanderId, ?array $warehouseIds = null): float
    {
        return $this->itemLocationRepository->getTotalStockByDemander($itemId, $demanderId, $warehouseIds);
    }

    /* ================= ALOKASI ================= */

    /**
     * $forUpdate mengunci baris lot. Dipakai saat stok akan dipotong
     * (approve, CONS) — bukan saat hanya menampilkan rekomendasi,
     * supaya lot tidak terkunci tanpa perlu.
     */
    public function allocateForTransfer(
        int $itemId,
        int $demanderId,
        array $warehouseIds,
        float $perPackage,
        float $packageNeeded,
        bool $forUpdate = false
    ): AllocationResult {
        $lots = $forUpdate
            ? $this->itemLocationRepository->getFefoLotsForTransferForUpdate($itemId, $demanderId, $warehouseIds, $perPackage)
            : $this->itemLocationRepository->getFefoLotsForTransfer($itemId, $demanderId, $warehouseIds, $perPackage);

        return $this->allocator->allocateByPackage($lots, $packageNeeded);
    }

    public function allocateForCons(
        int $itemId,
        int $warehouseId,
        int $demanderId,
        float $weightNeeded,
        bool $forUpdate = false
    ): AllocationResult {
        $lots = $forUpdate
            ? $this->itemLocationRepository->getFefoLotsForConsForUpdate($itemId, $warehouseId, $demanderId)
            : $this->itemLocationRepository->getFefoLotsForCons($itemId, $warehouseId, $demanderId);

        return $this->allocator->allocateByWeight($lots, $weightNeeded);
    }

    public function getAvailablePackageSizes(int $itemId, int $demanderId, array $warehouseIds): Collection
    {
        return $this->itemLocationRepository->getAvailablePackageSizes($itemId, $demanderId, $warehouseIds);
    }

    /* ================= MUTASI LOT ================= */

    /**
     * Potong berat dari sebuah lot.
     *
     * qty_package TIDAK disentuh — jumlah package selalu dihitung
     * ulang dari berat lewat accessor qty_package_display. Kalau
     * ikut dikurangi di sini, galat pembulatan akan menumpuk tiap
     * kali CONS terjadi.
     */
    public function deductLot(int $itemLocationId, float $qtyWeight): ItemLocation
    {
        // Kunci baris supaya dua proses bersamaan tidak sama-sama
        // membaca stok lama lalu keduanya lolos validasi.
        $lot = $this->itemLocationRepository->getByIdForUpdate($itemLocationId);

        $newWeight = round((float) $lot->qty_weight - $qtyWeight, 2);

        if ($newWeight < 0) {
            throw new \Exception(
                "Pengurangan melebihi stok lot " . ($lot->receiving_lot ?? $lot->vendor_lot ?? "#{$lot->id}") .
                    ". Stok tersedia: " . number_format((float) $lot->qty_weight, 2, ',', '.') . " kg."
            );
        }

        return $this->itemLocationRepository->update($itemLocationId, [
            'qty_weight' => $newWeight,
        ]);
    }

    /**
     * Buang lot yang rusak atau kedaluwarsa.
     *
     * Stok dinolkan dan lot ditandai dibuang — barisnya TIDAK
     * dihapus supaya jejak audit tetap ada dan detail transfer
     * lama masih bisa merujuknya.
     *
     * Ledger dicatat oleh pemanggil, bukan di sini, supaya service
     * ini tidak bergantung pada StockLedgerService.
     */
    public function disposeLot(int $itemLocationId, int $disposedBy, string $reason): ItemLocation
    {
        $lot = $this->itemLocationRepository->getByIdForUpdate($itemLocationId);

        if ($lot->disposed_at !== null) {
            throw new \Exception("Lot ini sudah dibuang sebelumnya.");
        }

        if ((float) $lot->qty_weight <= 0) {
            throw new \Exception("Lot ini sudah tidak memiliki stok.");
        }

        return $this->itemLocationRepository->update($itemLocationId, [
            'qty_weight'      => 0,
            'disposed_at'     => now(),
            'disposed_by'     => $disposedBy,
            'disposal_reason' => $reason,
        ]);
    }

    /**
     * Buat lot baru. Menggantikan addOrMergeLot() — lot hasil transfer
     * TIDAK PERNAH digabung dengan lot lama, supaya jejak tiap
     * pengiriman tetap terlihat dan FEFO di gudang tujuan tidak kabur.
     */
    public function addLot(array $lotData): ItemLocation
    {
        $perPackage = (float) ($lotData['qty_perpackage'] ?? 0);
        $package    = (float) ($lotData['qty_package'] ?? 0);

        if ($perPackage <= 0) {
            throw new \Exception("Ukuran per package harus lebih dari 0.");
        }

        if ($package <= 0) {
            throw new \Exception("Jumlah package harus lebih dari 0.");
        }

        // Berat SELALU hasil perkalian — tidak pernah diisi bebas.
        $lotData['qty_weight'] = round($perPackage * $package, 2);

        // Snapshot berat awal, jadi penanda lot masih utuh.
        $lotData['initial_weight'] = $lotData['qty_weight'];

        return $this->create($lotData);
    }

    public function generateReceivingLot($receivingDate)
    {
        $prefix = 'TFCO-';
        $date   = Carbon::parse($receivingDate)->format('ymd');

        return DB::transaction(function () use ($prefix, $date) {
            $lastRecord = ItemLocation::withTrashed()
                ->where('receiving_lot', 'like', $prefix . $date . '%')
                ->orderBy('receiving_lot', 'desc')
                ->lockForUpdate()
                ->first();

            $newNumber = $lastRecord
                ? ((int) substr($lastRecord->receiving_lot, strlen($prefix . $date))) + 1
                : 1;

            return $prefix . $date . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        });
    }

    /* ================= REPORT ================= */

    public function getGrandTotalStock(?int $demanderId = null): float
    {
        return $this->itemLocationRepository->getGrandTotalStock($demanderId);
    }

    public function getNearExpiring(int $days = 30, int $limit = 10, ?int $demanderId = null)
    {
        return $this->itemLocationRepository->getNearExpiring($days, $limit, $demanderId);
    }


    public function getStockSummaryByWarehouse(?int $demanderId = null)
    {
        return $this->itemLocationRepository->getStockSummaryByWarehouse($demanderId);
    }

    /* ================= PRIVATE ================= */

    /**
     * Isi exp_date HANYA kalau masih kosong.
     *
     * Versi lama selalu menimpa dengan production_date + 1 tahun.
     * Akibatnya lot yang exp-nya diinput manual (beda dari aturan
     * umum) akan berubah diam-diam, termasuk saat lot dipindahkan
     * lewat transfer.
     */
    private function prepareLotData(array $data): array
    {
        if (empty($data['exp_date']) && ! empty($data['production_date'])) {
            $data['exp_date'] = Carbon::parse($data['production_date'])
                ->addYear()
                ->toDateString();
        }

        if (empty($data['exp_by_receiving_at']) && ! empty($data['received_date'])) {
            $data['exp_by_receiving_at'] = Carbon::parse($data['received_date'])
                ->addYear()
                ->toDateString();
        }

        return $data;
    }

    public function getTransferLots(int $itemId, int $demanderId, array $warehouseIds, float $perPackage): Collection
    {
        return $this->itemLocationRepository->getFefoLotsForTransfer(
            $itemId,
            $demanderId,
            $warehouseIds,
            $perPackage
        );
    }

    public function getReservedPackage(int $itemId, int $demanderId, float $perPackage, ?int $excludeRequestId = null): float
    {
        return $this->itemLocationRepository->getReservedPackage($itemId, $demanderId, $perPackage, $excludeRequestId);
    }

    public function getDemanderStockSummary(int $demanderId, array $imcWarehouseIds): Collection
    {
        return $this->itemLocationRepository->getDemanderStockSummary($demanderId, $imcWarehouseIds);
    }
}
