<?php

namespace App\Repositories\Eloquents;

use App\Models\Department;
use App\Repositories\Interfaces\DepartmentRepositoryInterface;

class DepartmentRepository implements DepartmentRepositoryInterface
{
    protected Department $model;

    public function  __construct(Department $department)
    {
        $this->model = $department;
    }

    public function getAll()
    {
        return $this->model->query();
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
        $department = $this->getById($id);
        $department->update($data);
        return $department;
    }

    public function delete(int $id)
    {
        $department = $this->getById($id);
        $department->delete();
    }
}
