<?php

namespace App\Repositories\Eloquents;

use App\Models\ItemLocation;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;


class ItemLocationRepository implements ItemLocationRepositoryInterface
{
    protected ItemLocation $model;
    public function __construct(ItemLocation $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with(['item', 'warehouse']);
    }

    public function getById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $itemLocation = $this->getById($id);
        $itemLocation->update($data);
        return $itemLocation;
    }

    public function delete(int $id)
    {
        $itemLocation = $this->getById($id);
        $itemLocation->delete();
    }

    public function getTotalStock(int $itemId, int $warehouseId): float
    {
        return (float) $this->model->where('item_id', $itemId)->where('warehouse_id', $warehouseId)->sum('qty_weight');
    }

    public function getTotalStockAllWarehouses(int $itemId): float
    {
        return (float) $this->model->where('item_id', $itemId)->sum('qty_weight');
    }

    public function getFefoLots(int $itemId, int $warehouseId)
    {
        return $this->model->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->available()
            ->fefo()
            ->get();
    }

    public function getFefoLotsAcrossWarehouses(int $itemId, ?int $excludeWarehouseId = null)
    {
        return $this->model
            ->with('warehouse')
            ->where('item_id', $itemId)
            ->available()
            ->when($excludeWarehouseId, fn($q) => $q->where('warehouse_id', '!=', $excludeWarehouseId))
            ->fefo()
            ->get();
    }

    public function findMatchingLot(int $itemId, int $warehouseId, ?string $vendorLot, ?string $expDate)
    {
        return $this->model
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->where('vendor_lot', $vendorLot)
            ->where('exp_date', $expDate)
            ->first();
    }
}
