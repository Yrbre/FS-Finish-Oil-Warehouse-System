<?php

namespace App\Services;

use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use App\Services\Interfaces\WarehouseServiceInterface;

class WarehouseService implements WarehouseServiceInterface
{
    protected WarehouseRepositoryInterface $warehouseRepository;
    public function __construct(WarehouseRepositoryInterface $warehouseRepository)
    {
        $this->warehouseRepository = $warehouseRepository;
    }

    public function getAll()
    {
        return $this->warehouseRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->warehouseRepository->getById($id);
    }

    public function getByDepartment(int $departmentId)
    {
        return $this->warehouseRepository->getByDepartment($departmentId);
    }

    public function create(array $data)
    {
        return $this->warehouseRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->warehouseRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $warehouse = $this->getById($id);

        $hasStock = $warehouse->itemLocations()->where('qty_weight', '>', 0)->exists();

        if ($hasStock) {
            throw new \Exception('Warehouse Masih Memiliki Stock, Tidak Dapat Dihapus');
        }

        return $this->warehouseRepository->delete($id);
    }
}
