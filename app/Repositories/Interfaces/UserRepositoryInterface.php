<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function getAll();
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    public function isTransferApprover(int $userId): bool;
    public function addTransferApprover(int $userId): void;
    public function removeTransferApprover(int $userId): void;
}
