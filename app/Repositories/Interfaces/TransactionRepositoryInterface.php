<?php

namespace App\Repositories\Interfaces;

interface TransactionRepositoryInterface
{
    public function getAll();
    public function getById(int $id);
    public function create(array $data);
    public function update(int $id, array $data);
    public function delete(int $id);

    /**
     * Update kolom saldo (bb_qty/eb_qty) tanpa memicu event model.
     * Dipakai saat sinkronisasi hasil recalculate dari stock_ledger.
     */
    public function syncBalance(int $id, float $bbQty, float $ebQty): void;
}
