<?php

namespace App\Repositories\Eloquents;

use App\Models\Warehouse;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;

class WarehouseRepository implements WarehouseRepositoryInterface
{
    protected Warehouse $model;
    public function __construct(Warehouse $warehouse)
    {
        $this->model = $warehouse;
    }

    public function getAll()
    {
        return $this->model->with('department');
    }

    public function getById(int $id)
    {
        return $this->model->findOrFail($id);
    }

    public function getByDepartment(int $departmentId)
    {
        return $this->model->where('department_id', $departmentId)->get();
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $warehouse = $this->getById($id);
        $warehouse->update($data);
        return $warehouse;
    }

    public function delete(int $id)
    {
        $warehouse = $this->getById($id);
        $warehouse->delete();
    }

    public function getIdsByDepartmentCode(string $departmentCode): array
    {
        return $this->model
            ->whereHas('department', fn($q) => $q->where('code', $departmentCode))
            ->pluck('id')
            ->all();
    }
}
