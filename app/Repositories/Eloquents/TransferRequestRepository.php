<?php

namespace App\Repositories\Eloquents;

use App\Models\TransferApprover;
use App\Models\TransferRequest;
use App\Models\TransferRequestDetail;
use App\Repositories\Interfaces\TransferRequestRepositoryInterface;

class TransferRequestRepository implements TransferRequestRepositoryInterface
{
    protected TransferRequest $model;

    public function __construct(TransferRequest $model)
    {
        $this->model = $model;
    }

    public function getAll()
    {
        return $this->model->with([
            'destinationWarehouse',
            'department',
            'requester',
            'items.item',
        ])->withCount('items');
    }

    public function getById(int $id)
    {
        return $this->model->with([
            'department',
            'destinationWarehouse',
            'requester',
            'approver',
            'receiver',
            'shipper',
            'receiptOfGoods.responsibility',
            'items.item',
            'items.details.sourceWarehouse',
            'items.rejecter',
            'items.canceller',
        ])->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function update(int $id, array $data)
    {
        $transferRequest = $this->model->findOrFail($id);
        $transferRequest->update($data);

        return $transferRequest;
    }

    public function delete(int $id)
    {
        return $this->model->findOrFail($id)->delete();
    }

    public function createDetails(array $details): void
    {
        foreach ($details as $detail) {
            TransferRequestDetail::create($detail);
        }
    }

    public function getDetails(int $transferRequestId)
    {
        return TransferRequestDetail::with(['itemLocation', 'sourceWarehouse'])
            ->where('transfer_request_id', $transferRequestId)
            ->get();
    }

    public function isApprover(int $userId): bool
    {
        return TransferApprover::where('user_id', $userId)->exists();
    }

    public function getByIdForUpdate(int $id)
    {
        return $this->model->lockForUpdate()->findOrFail($id);
    }
}
