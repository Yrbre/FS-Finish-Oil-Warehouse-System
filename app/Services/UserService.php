<?php

namespace App\Services;

use App\Repositories\Interfaces\UserRepositoryInterface;
use App\Services\Interfaces\UserServiceInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService implements UserServiceInterface
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function getAll()
    {
        return $this->userRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->userRepository->getById($id);
    }

    public function create(array $data)
    {
        return DB::transaction(function () use ($data) {
            $role                = $data['role'];
            $isTransferApprover  = (bool) ($data['is_transfer_approver'] ?? false);
            $data['can_issue_receipt'] = (bool) ($data['can_issue_receipt'] ?? false);

            unset($data['role'], $data['is_transfer_approver']);

            $data['password'] = Hash::make($data['password']);

            $user = $this->userRepository->create($data);

            // Aturan bisnis: 1 user = 1 role
            $user->assignSingleRole($role);

            if ($isTransferApprover) {
                $this->userRepository->addTransferApprover($user->id);
            }

            return $user;
        });
    }

    public function update(int $id, array $data)
    {
        return DB::transaction(function () use ($id, $data) {
            $role               = $data['role'] ?? null;
            $isTransferApprover = array_key_exists('is_transfer_approver', $data)
                ? (bool) $data['is_transfer_approver']
                : null;

            unset($data['role'], $data['is_transfer_approver']);

            // Password kosong = tidak diubah
            if (empty($data['password'])) {
                unset($data['password']);
            } else {
                $data['password'] = Hash::make($data['password']);
            }

            $user = $this->userRepository->update($id, $data);

            if ($role) {
                $user->assignSingleRole($role);
            }

            if (! is_null($isTransferApprover)) {
                if ($isTransferApprover) {
                    $this->userRepository->addTransferApprover($id);
                } else {
                    $this->userRepository->removeTransferApprover($id);
                }
            }

            return $user;
        });
    }

    public function delete(int $id)
    {
        return $this->userRepository->delete($id);
    }
}
