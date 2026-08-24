<?php

namespace App\Repositories\Eloquents;

use App\Models\ItemLocation;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use Illuminate\Support\Collection;

class ItemLocationRepository implements ItemLocationRepositoryInterface
{
    protected ItemLocation $model;

    public function __construct(ItemLocation $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model
            ->with(['item', 'warehouse', 'demander'])
            ->available();
    }

    /**
     * withTrashed karena transfer_request_details bisa merujuk lot
     * yang sudah di-soft-delete — tanpa ini, membuka detail transfer
     * lama akan melempar 404.
     */
    public function getById(int $id)
    {
        return $this->model->withTrashed()->findOrFail($id);
    }

    /**
     * Versi dengan row lock. Dipakai saat akan memotong stok, supaya
     * dua proses bersamaan tidak sama-sama lolos validasi.
     * Harus dipanggil di dalam DB::transaction().
     */
    public function getByIdForUpdate(int $id)
    {
        return $this->model->lockForUpdate()->findOrFail($id);
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
        $this->getById($id)->delete();
    }

    /* ================= STOK ================= */

    public function getTotalStock(int $itemId, int $warehouseId, ?int $demanderId = null): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->when($demanderId, fn($q) => $q->where('demander_id', $demanderId))
            ->available()
            ->sum('qty_weight');
    }

    public function getTotalStockByDepartment(?int $itemId, int $departmentId): float
    {
        return (float) $this->model
            ->whereHas('warehouse', fn($q) => $q->where('department_id', $departmentId))
            ->when($itemId !== null, fn($q) => $q->where('item_id', $itemId))
            ->available()
            ->sum('qty_weight');
    }

    public function getTotalStockAllWarehouses(int $itemId): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->available()
            ->sum('qty_weight');
    }

    /**
     * Stok milik satu department, lintas gudang.
     *
     * Tanpa $warehouseIds: seluruh stok miliknya di mana pun berada
     * (gudang sendiri + titipan di IMC) — ini yang dipakai min-stock.
     * Dengan $warehouseIds: dibatasi gudang tertentu saja.
     */
    public function getTotalStockByDemander(int $itemId, int $demanderId, ?array $warehouseIds = null): float
    {
        return (float) $this->model
            ->where('item_id', $itemId)
            ->where('demander_id', $demanderId)
            ->when($warehouseIds, fn($q) => $q->whereIn('warehouse_id', $warehouseIds))
            ->available()
            ->sum('qty_weight');
    }

    /* ================= FEFO ================= */

    public function getFefoLotsForTransfer(
        int $itemId,
        int $demanderId,
        array $warehouseIds,
        float $perPackage
    ): Collection {
        if (empty($warehouseIds)) {
            return collect();
        }

        return $this->model
            ->with('warehouse')
            ->where('item_id', $itemId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->ownedBy($demanderId)
            ->ofPackageSize($perPackage)
            ->available()
            ->fefo()
            ->get();
    }

    public function getFefoLotsForCons(int $itemId, int $warehouseId, int $demanderId): Collection
    {
        return $this->model
            ->where('item_id', $itemId)
            ->where('warehouse_id', $warehouseId)
            ->ownedBy($demanderId)
            ->available()
            ->fefo()
            ->get();
    }

    public function getAvailablePackageSizes(int $itemId, int $demanderId, array $warehouseIds): Collection
    {
        if (empty($warehouseIds)) {
            return collect();
        }

        return $this->model
            ->where('item_id', $itemId)
            ->whereIn('warehouse_id', $warehouseIds)
            ->ownedBy($demanderId)
            ->available()
            ->selectRaw('
                qty_perpackage,
                MIN(package) as package,
                SUM(qty_weight) as total_weight,
                COUNT(*) as lot_count,
                MIN(exp_date) as nearest_exp
            ')
            ->groupBy('qty_perpackage')
            ->orderBy('qty_perpackage')
            ->get()
            ->map(function ($row) {
                $per = (float) $row->qty_perpackage;

                return (object) [
                    'qty_perpackage'    => $per,
                    'package'           => $row->package,
                    'total_weight'      => (float) $row->total_weight,
                    // Package utuh yang benar-benar bisa dikirim.
                    'available_package' => $per > 0 ? floor((float) $row->total_weight / $per) : 0,
                    'lot_count'         => (int) $row->lot_count,
                    'nearest_exp'       => $row->nearest_exp,
                ];
            });
    }

    /* ================= REPORT ================= */

    public function getGrandTotalStock(): float
    {
        return (float) $this->model->available()->sum('qty_weight');
    }

    public function getNearExpiring(int $days = 30, int $limit = 10)
    {
        return $this->model
            ->with(['item', 'warehouse', 'demander'])
            ->available()
            ->whereNotNull('exp_date')
            ->where('exp_date', '<=', now()->addDays($days)->toDateString())
            ->orderBy('exp_date', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getStockSummaryByWarehouse()
    {
        return $this->model
            ->join('warehouses', 'warehouses.id', '=', 'item_locations.warehouse_id')
            ->whereNull('item_locations.deleted_at')
            ->whereNull('item_locations.disposed_at')
            ->where('item_locations.qty_weight', '>', 0)
            ->selectRaw('
                warehouses.id as warehouse_id,
                warehouses.name as warehouse_name,
                warehouses.tag as warehouse_tag,
                SUM(item_locations.qty_weight) as total_stock,
                COUNT(DISTINCT item_locations.item_id) as item_count
            ')
            ->groupBy('warehouses.id', 'warehouses.name', 'warehouses.tag')
            ->orderBy('warehouses.name')
            ->get();
    }
}
