<?php

namespace App\Services;

use App\Models\ItemLocation;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

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
        $data = $this->applyExpiryRule($data);


        return $this->itemLocationRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        $data = $this->applyExpiryRule($data);


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

    public function getTotalStockByDepartment(int | null $itemId, int $departmentId): float
    {
        return $this->itemLocationRepository->getTotalStockByDepartment($itemId, $departmentId);
    }

    public function getTotalStockAllWarehouses(int $itemId): float
    {
        return $this->itemLocationRepository->getTotalStockAllWarehouses($itemId);
    }

    public function getGrandTotalStock(): float
    {
        return $this->itemLocationRepository->getGrandTotalStock();
    }

    public function getNearExpiring(int $days = 30, int $limit = 10)
    {
        return $this->itemLocationRepository->getNearExpiring($days, $limit);
    }

    public function getStockSummaryByWarehouse()
    {
        return $this->itemLocationRepository->getStockSummaryByWarehouse();
    }

    public function allocateFefo(int $itemId, int $warehouseId, float $qtyNeeded): array
    {
        $lots = $this->itemLocationRepository->getFefoLots($itemId, $warehouseId);

        $allocation = $this->buildAllocation($lots, $qtyNeeded, $remaining);

        if ($remaining > 0) {
            throw new \Exception(
                "Stok tidak mencukupi. Kurang: " . $remaining . " unit."
            );
        }

        return $allocation;
    }

    public function allocateFefoAcrossWarehouses(
        int $itemId,
        float $qtyNeeded,
        array $warehouseIds,
        float &$remainingQty
    ): array {
        $lots = $this->itemLocationRepository
            ->getFefoLotsAcrossWarehouses($itemId, $warehouseIds);

        $allocation = $this->buildAllocation($lots, $qtyNeeded, $remaining);

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

        return $this->itemLocationRepository->update($itemLocationId, [
            'qty_weight' => $newQty,
        ]);
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

    public function getAvailableLotsAcrossWarehouses(int $itemId, array $warehouseIds)
    {
        return $this->itemLocationRepository->getFefoLotsAcrossWarehouses($itemId, $warehouseIds);
    }

    private function applyExpiryRule(array $data): array
    {
        if (! empty($data['production_date'])) {
            $data['exp_date'] = \Carbon\Carbon::parse($data['production_date'])
                ->addYear()
                ->toDateString();
        }

        return $data;
    }

    private function buildAllocation($lots, float $qtyNeeded, ?float &$remaining): array
    {
        $remaining  = $qtyNeeded;
        $allocation = [];

        foreach ($lots as $lot) {
            if ($remaining <= 0) {
                break;
            }

            $take = min((float) $lot->qty_weight, $remaining);

            $allocation[] = [
                'item_location' => $lot,
                'qty_to_take'   => $take,
            ];

            $remaining -= $take;
        }

        return $allocation;
    }

    public function generateReceivingLot($receivingDate)
    {
        $prefix = 'TFCO-';
        $receivingDate = Carbon::parse($receivingDate)->format('ymd');

        return DB::transaction(function () use ($prefix, $receivingDate) {
            $lastRecord = ItemLocation::withTrashed()
                ->where('receiving_lot', 'like', $prefix . $receivingDate . '%')
                ->orderBy('receiving_lot', 'desc')
                ->lockForUpdate()
                ->first();


            $newNumber = $lastRecord
                ? ((int) substr($lastRecord->receiving_lot, strlen($prefix . $receivingDate))) + 1
                : 1;

            return $prefix . $receivingDate . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
        });
    }
}
