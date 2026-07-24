<?php

namespace App\Repositories\Eloquents;

use App\Models\Transaction;
use App\Repositories\Interfaces\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected Transaction $model;
    public function __construct(Transaction $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with(['item', 'warehouse', 'creator']);
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
        $transaction = $this->getById($id);
        $transaction->update($data);
        return $transaction;
    }

    public function delete(int $id)
    {
        $transaction = $this->getById($id);
        $transaction->delete();
    }

    public function syncBalance(int $id, float $bbQty, float $ebQty): void
    {
        $this->model->where('id', $id)->update([
            'bb_qty' => $bbQty,
            'eb_qty' => $ebQty
        ]);
    }
}
