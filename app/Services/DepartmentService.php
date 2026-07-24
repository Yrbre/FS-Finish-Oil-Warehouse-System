<?php

namespace App\Services;

use App\Repositories\Interfaces\DepartmentRepositoryInterface;
use App\Services\Interfaces\DepartmentServiceInterface;

class DepartmentService implements DepartmentServiceInterface
{
    protected DepartmentRepositoryInterface $departmentRepository;
    public function __construct(DepartmentRepositoryInterface $departmentRepository)
    {
        $this->departmentRepository = $departmentRepository;
    }

    public function getAll()
    {
        return $this->departmentRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->departmentRepository->getById($id);
    }

    public function create(array $data)
    {
        return $this->departmentRepository->create($data);
    }

    public function update(int $id, array $data)
    {
        return $this->departmentRepository->update($id, $data);
    }

    public function delete(int $id)
    {
        $department = $this->getById($id);

        if ($department->warehouses()->exists()) {
            throw new \Exception('Department Masih Memiliki Warehouse, Tidak Dapat Dihapus');
        }

        return $this->departmentRepository->delete($id);
    }
}
