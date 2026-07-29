<?php

namespace App\Repositories\Eloquents;

use App\Models\TransferApprover;
use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected User $model;

    public function __construct(User $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with(['department', 'roles']);
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
        $user = $this->getById($id);
        $user->update($data);

        return $user;
    }

    public function delete(int $id)
    {
        return $this->getById($id)->delete();
    }

    public function isTransferApprover(int $userId): bool
    {
        return TransferApprover::where('user_id', $userId)->exists();
    }

    public function addTransferApprover(int $userId): void
    {
        TransferApprover::firstOrCreate(['user_id' => $userId]);
    }

    public function removeTransferApprover(int $userId): void
    {
        TransferApprover::where('user_id', $userId)->delete();
    }
}
