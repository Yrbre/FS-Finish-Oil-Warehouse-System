<?php

namespace App\Repositories\Interfaces;

interface WarehouseRepositoryInterface
{
    public function getAll();
    public function getById(int $id);
    public function getByDepartment(int $departmentId);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);
    /**
     * ID semua warehouse milik department dengan kode tertentu.
     * Dipakai untuk membatasi FEFO lintas gudang hanya ke gudang IMC.
     */
    public function getIdsByDepartmentCode(string $departmentCode): array;
}
