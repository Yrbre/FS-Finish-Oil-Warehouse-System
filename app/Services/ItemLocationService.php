<?php

namespace App\Services;


use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;

class ItemLocationService implements ItemLocationServiceInterface
{
    protected ItemLocationRepositoryInterface $itemLocationRepository;
    public function __construct(ItemLocationRepositoryInterface $itemLocationRepository)
    {
        $this->itemLocationRepository = $itemLocationRepository;
    }

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
        return $this->itemLocationRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->itemLocationRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        return $this->itemLocationRepository->delete($id);
    }

    public function getTotalStock(int $itemId, int $warehouseId): float
    {
        return $this->itemLocationRepository->getTotalStock($itemId, $warehouseId);
    }

    public function getTotalStockAllWarehouses(int $itemId): float
    {
        return $this->itemLocationRepository->getTotalStockAllWarehouses($itemId);
    }

    public function allocateFefo(int $itemId, int $warehouseId, float $qtyNeeded): array
    {
        $lots = $this->itemLocationRepository->getFefoLots($itemId, $warehouseId);

        $allocation = $this->buildAllocation($lots, $qtyNeeded, $remaining);

        if ($remaining > 0) {
            throw new \Exception(
                "Stok tidak mencukupi. Kurang: " . $remaining
            );
        }

        return $allocation;
    }

    public function allocateFefoAcrossWarehouses(int $itemId, float $qtyNeeded, ?int $excludeWarehouseId, float &$remaining): array
    {
        $lots = $this->itemLocationRepository->getFefoLotsAcrossWarehouses($itemId, $excludeWarehouseId);

        $allocation = $this->buildAllocation($lots, $qtyNeeded, $remaining);

        // Sengaja tidak throw — pemanggil yang menentukan apa yang terjadi
        // kalau stok seluruh gudang tidak mencukupi.
        $remainingQty = $remaining;
        return $allocation;
    }


    public function deductLot(int $itemLocationId, float $qty)
    {
        $lot = $this->itemLocationRepository->getById($itemLocationId);
        $newQty = (float) $lot->qty_weight - $qty;
        if ($newQty < 0) {
            throw new \Exception(
                "Pengurangan melebihi stok lot " . ($lot->vendor_lot ?? '-') . ". " .
                    "Stok tersedia: " . number_format((float) $lot->qty_weight, 2, ',', '.')
            );
        }
        return $this->itemLocationRepository->update($itemLocationId, ['qty_weight' => $newQty]);
    }

    public function addOrMergeLot(int $itemId, int $warehouseId, array $lotData)
    {
        $existing = $this->itemLocationRepository->findMatchingLot(
            $itemId,
            $warehouseId,
            $lotData['vendor_lot'] ?? null,
            $lotData['exp_date'] ?? null
        );

        if ($existing) {
            // Lot yang sama sudah ada di gudang tujuan — cukup tambah qty
            return $this->itemLocationRepository->update($existing->id, [
                'qty_weight' => (float) $existing->qty_weight + (float) $lotData['qty_weight'],
                'qty_unit'   => (float) $existing->qty_unit + (float) ($lotData['qty_unit'] ?? 0),
            ]);
        }

        return $this->itemLocationRepository->create(array_merge($lotData, [
            'item_id'            => $itemId,
            'warehouse_id'       => $warehouseId,
            'is_warehouse_stock' => true,
        ]));
    }

    /**
     * Logika inti FEFO: ambil dari lot paling dekat expired,
     * kalau belum cukup lanjut ke lot berikutnya.
     */
    private function buildAllocation(object $lots, float $qtyNeeded, ?float &$remaining): array
    {
        $remaining = $qtyNeeded;
        $allocation = [];

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }
            $take = min((float) $lot->qty_weight, $remaining);
            $allocation[] = [
                'item_location' => $lot,
                'qty_to_take' => $take,
            ];
            $remaining -= $take;
        }
        return $allocation;
    }
}
