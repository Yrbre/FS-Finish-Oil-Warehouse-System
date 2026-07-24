<?php

namespace App\Services\Interfaces;

interface WarehouseServiceInterface
{
    public function getAll();
    public function getById(int $id);
    public function getByDepartment(int $departmentId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
}
