<?php

namespace App\Services\Interfaces;

interface UserServiceInterface
{
    public function getAll();
    public function getById(int $id);

    /**
     * Buat user baru + assign 1 role + (opsional) tandai sebagai
     * approver transfer (IMC). $data['role'] wajib ada,
     * $data['is_transfer_approver'] boolean opsional.
     */
    public function create(array $data);

    /**
     * Update data user. Kalau password dikirim kosong, tidak diubah.
     * Role & status approver ikut disinkronkan kalau dikirim.
     */
    public function update(int $id, array $data);

    public function delete(int $id);
}
