<?php

namespace App\Repositories\Interfaces;

interface TransferRequestRepositoryInterface
{
    public function getAll();
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    /**
     * Simpan breakdown lot hasil FEFO (bulk insert).
     */
    public function createDetails(array $details): void;

    /**
     * Semua breakdown lot milik 1 Permintaan Kirim Barang .
     */
    public function getDetails(int $transferRequestId);

    /**
     * Apakah user ini terdaftar sebagai approver transfer (IMC)?
     */
    public function isApprover(int $userId): bool;


    /** Versi dengan row lock — cegah dua approver memproses bersamaan. */
    public function getByIdForUpdate(int $id);

    /**
     * Rekap transfer yang sudah SELESAI (received) dalam rentang
     * tanggal, untuk widget dashboard IMC.
     */
    public function getReceivedSummary(string $from, string $to): array;
}
