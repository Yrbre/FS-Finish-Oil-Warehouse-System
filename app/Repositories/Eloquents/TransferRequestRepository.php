<?php

namespace App\Repositories\Eloquents;

use App\Models\TransferApprover;
use App\Models\TransferRequest;
use App\Models\TransferRequestDetail;
use App\Models\TransferRequestItem;
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

    /**
     * Rekap transfer yang sudah selesai diterima.
     *
     * Dihitung dari received_date (tanggal efektif barang sampai),
     * bukan created_at — supaya cocok dengan periode operasional
     * meski tanggalnya di-backdate.
     *
     * Item yang ditolak/dibatalkan tidak ikut dihitung karena
     * barangnya memang tidak pernah dikirim.
     */
    public function getReceivedSummary(string $from, string $to): array
    {
        $base = $this->model
            ->where('status', TransferRequest::STATUS_RECEIVED)
            ->whereBetween('received_date', [$from, $to]);

        $totalRequest = (clone $base)->count();
        $requestIds   = (clone $base)->pluck('id');

        if ($requestIds->isEmpty()) {
            return [
                'total_request' => 0,
                'total_item'    => 0,
                'total_package' => 0,
                'total_weight'  => 0,
                'requests'      => collect(),
            ];
        }

        $itemIds = TransferRequestItem::whereIn('transfer_request_id', $requestIds)
            ->where('status', TransferRequestItem::STATUS_APPROVED)
            ->pluck('id');

        $totals = TransferRequestDetail::whereIn('transfer_request_item_id', $itemIds)
            ->selectRaw('COALESCE(SUM(package_taken),0) as pkg, COALESCE(SUM(qty_taken),0) as kg')
            ->first();

        // Dibatasi 50 — dashboard untuk pantauan cepat, bukan laporan.
        $requests = $this->model
            ->with([
                'department',
                'destinationWarehouse',
                'requester',
                'receiptOfGoods',
                'items' => fn($q) => $q->where('status', TransferRequestItem::STATUS_APPROVED),
                'items.item',
                'items.details',
            ])
            ->where('status', TransferRequest::STATUS_RECEIVED)
            ->whereBetween('received_date', [$from, $to])
            ->orderByDesc('received_date')
            ->orderByDesc('id')
            ->limit(50)
            ->get()
            ->map(fn($r) => [
                'id'            => $r->id,
                'code'          => $r->transfer_code,
                'department'    => $r->department->code ?? '-',
                'destination'   => ($r->destinationWarehouse->name ?? '-') . ' - ' . ($r->destinationWarehouse->tag ?? ''),
                'requester'     => $r->requester->name ?? '-',
                'received_date' => $r->received_date?->format('d-m-Y'),
                'letter_number' => $r->receiptOfGoods->letter_number ?? null,
                'url'           => route('transfer-requests.show', $r->id),
                'total_package' => (int) $r->items->sum(fn($i) => $i->details->sum('package_taken')),
                'total_weight'  => (float) $r->items->sum(fn($i) => $i->details->sum('qty_taken')),
                'items'         => $r->items->map(fn($i) => [
                    'item_no'    => $i->item->item_no,
                    'item_desc'  => $i->item->item_desc,
                    'uom'        => $i->item->item_uom,
                    'perpackage' => (float) $i->requested_perpackage,
                    'package'    => (int) $i->details->sum('package_taken'),
                    'weight'     => (float) $i->details->sum('qty_taken'),
                ])->values(),
            ]);

        return [
            'total_request' => $totalRequest,
            'total_item'    => $itemIds->count(),
            'total_package' => (float) ($totals->pkg ?? 0),
            'total_weight'  => (float) ($totals->kg ?? 0),
            'requests'      => $requests,
        ];
    }
}
