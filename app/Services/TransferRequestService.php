<?php

namespace App\Services;

use App\Models\StockLedger;
use App\Models\TransferRequest;
use App\Repositories\Interfaces\TransferRequestRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransferRequestService implements TransferRequestServiceInterface
{
    protected TransferRequestRepositoryInterface $transferRequestRepository;
    protected ItemLocationServiceInterface $itemLocationService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected WarehouseServiceInterface $warehouseService;

    public function __construct(
        TransferRequestRepositoryInterface $transferRequestRepository,
        ItemLocationServiceInterface $itemLocationService,
        StockLedgerServiceInterface $stockLedgerService,
        WarehouseServiceInterface $warehouseService
    ) {
        $this->transferRequestRepository = $transferRequestRepository;
        $this->itemLocationService       = $itemLocationService;
        $this->stockLedgerService        = $stockLedgerService;
        $this->warehouseService          = $warehouseService;
    }

    public function getAll()
    {
        return $this->transferRequestRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->transferRequestRepository->getById($id);
    }

    public function create(array $data, int $requestedBy)
    {
        $data['transfer_code'] = TransferRequest::generateTransferCode();
        $data['status']        = TransferRequest::STATUS_NEW;
        $data['requested_by']  = $requestedBy;

        return $this->transferRequestRepository->create($data);
    }

    public function getRecommendation(int $id): array
    {
        $request = $this->transferRequestRepository->getById($id);

        // Sumber transfer hanya boleh dari gudang milik department IMC,
        // dan gudang tujuan tidak boleh jadi sumber untuk dirinya sendiri.
        $sourceWarehouseIds = array_values(array_diff(
            $this->warehouseService->getIdsByDepartmentCode(TransferRequest::SOURCE_DEPARTMENT_CODE),
            [(int) $request->destination_warehouse_id]
        ));

        $remainingQty = 0.0;

        $allocation = $this->itemLocationService->allocateFefoAcrossWarehouses(
            (int) $request->item_id,
            (float) $request->requested_qty,
            $sourceWarehouseIds,
            $remainingQty
        );

        return [
            'allocation'    => $allocation,
            'remaining_qty' => $remainingQty,
            'is_fulfilled'  => $remainingQty <= 0,
        ];
    }

    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null)
    {
        return DB::transaction(function () use ($id, $approvedBy, $effectiveDate) {
            $request = $this->transferRequestRepository->getById($id);

            $this->guardApprover($approvedBy);

            if ($request->status !== TransferRequest::STATUS_NEW) {
                throw new \Exception("Request ini sudah diproses sebelumnya.");
            }

            $recommendation = $this->getRecommendation($id);

            if (! $recommendation['is_fulfilled']) {
                throw new \Exception(
                    "Stok di gudang IMC tidak mencukupi. Kurang: " .
                        number_format($recommendation['remaining_qty'], 2, ',', '.')
                );
            }

            $transDate = $effectiveDate ? Carbon::parse($effectiveDate) : now();

            // Kelompokkan alokasi FEFO per warehouse asal — supaya bb_qty
            // dihitung 1x per warehouse (kondisi SEBELUM lot-lot di warehouse
            // itu dikurangi), bukan per lot.
            $groupedByWarehouse = collect($recommendation['allocation'])
                ->groupBy(fn($row) => $row['item_location']->warehouse_id);

            $details = [];

            foreach ($groupedByWarehouse as $warehouseId => $rows) {
                $warehouseId = (int) $warehouseId;

                // bb_qty = stok warehouse ini SEBELUM dikurangi transfer ini
                $bbQty = $this->itemLocationService->getTotalStock((int) $request->item_id, $warehouseId);

                $totalTakenFromWarehouse = 0.0;

                foreach ($rows as $row) {
                    $lot   = $row['item_location'];
                    $taken = $row['qty_to_take'];

                    $this->itemLocationService->deductLot($lot->id, $taken);
                    $totalTakenFromWarehouse += $taken;

                    $unitRatio = (float) $lot->qty_weight > 0 ? $taken / (float) $lot->qty_weight : 0;

                    $details[] = [
                        'transfer_request_id' => $request->id,
                        'item_location_id'    => $lot->id,
                        'source_warehouse_id' => $warehouseId,
                        'vendor_lot'          => $lot->vendor_lot,
                        'exp_date'            => $lot->exp_date?->toDateString(),
                        'production_date'     => $lot->production_date?->toDateString(),
                        'package'             => $lot->package,
                        'qty_taken'           => $taken,
                        'qty_unit'            => round((float) $lot->qty_unit * $unitRatio, 2),
                        'created_at'          => now(),
                        'updated_at'          => now(),
                    ];
                }

                $ebQty = $bbQty - $totalTakenFromWarehouse;

                // Arsip riwayat — bb/eb ini kondisi NYATA warehouse itu saat
                // transfer diproses, bukan hasil recalculate.
                $this->stockLedgerService->record([
                    'item_id'      => $request->item_id,
                    'warehouse_id' => $warehouseId,
                    'trans_date'   => $transDate->toDateString(),
                    'in_qty'       => 0,
                    'out_qty'      => $totalTakenFromWarehouse,
                    'bb_qty'       => $bbQty,
                    'eb_qty'       => $ebQty,
                    'doc_type'     => StockLedger::DOC_TRANSFER_OUT,
                    'ref_type'     => StockLedger::REF_TRANSFER_OUT,
                    'ref_id'       => $request->id,
                ]);
            }

            $this->transferRequestRepository->createDetails($details);

            return $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_IN_TRANSIT,
                'approved_by'   => $approvedBy,
                'approved_at'   => now(),
                'approved_date' => $transDate->toDateString(),
            ]);
        });
    }

    public function receive(int $id, int $receivedBy, ?string $effectiveDate = null)
    {
        return DB::transaction(function () use ($id, $receivedBy, $effectiveDate) {
            $request = $this->transferRequestRepository->getById($id);

            if ($request->status !== TransferRequest::STATUS_IN_TRANSIT) {
                throw new \Exception("Barang belum dikirim atau sudah diterima sebelumnya.");
            }

            $transDate = $effectiveDate ? Carbon::parse($effectiveDate) : now();

            $destWarehouseId = (int) $request->destination_warehouse_id;

            // Lot baru di gudang tujuan diperuntukan bagi department yang
            // MENGAJUKAN transfer ini — bukan department asal lot sumber.
            $demanderId = $request->department_id;

            // bb_qty = stok gudang tujuan SEBELUM barang ini masuk
            $bbQty = $this->itemLocationService->getTotalStock((int) $request->item_id, $destWarehouseId);

            $details  = $this->transferRequestRepository->getDetails($id);
            $totalQty = 0.0;

            foreach ($details as $detail) {
                $destLot = $this->itemLocationService->addOrMergeLot(
                    (int) $request->item_id,
                    $destWarehouseId,
                    [
                        'demander_id'     => $demanderId,
                        'vendor_lot'      => $detail->vendor_lot,
                        'exp_date'        => $detail->exp_date?->toDateString(),
                        'production_date' => $detail->production_date?->toDateString(),
                        'package'         => $detail->package,
                        'qty_weight'      => $detail->qty_taken,
                        'qty_unit'        => $detail->qty_unit,
                        'received_date'   => $transDate->toDateString(),
                    ]
                );

                $detail->update(['dest_item_location_id' => $destLot->id]);

                $totalQty += (float) $detail->qty_taken;
            }

            $ebQty = $bbQty + $totalQty;

            $this->stockLedgerService->record([
                'item_id'      => $request->item_id,
                'warehouse_id' => $destWarehouseId,
                'trans_date'   => $transDate->toDateString(),
                'in_qty'       => $totalQty,
                'out_qty'      => 0,
                'bb_qty'       => $bbQty,
                'eb_qty'       => $ebQty,
                'doc_type'     => StockLedger::DOC_TRANSFER_IN,
                'ref_type'     => StockLedger::REF_TRANSFER_IN,
                'ref_id'       => $request->id,
            ]);

            return $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_RECEIVED,
                'received_by'   => $receivedBy,
                'received_at'   => now(),
                'received_date' => $transDate->toDateString(),
            ]);
        });
    }

    public function reject(int $id, int $rejectedBy, string $reason)
    {
        $request = $this->transferRequestRepository->getById($id);

        $this->guardApprover($rejectedBy);

        if ($request->status !== TransferRequest::STATUS_NEW) {
            throw new \Exception("Request ini sudah diproses, tidak dapat ditolak.");
        }

        return $this->transferRequestRepository->update($id, [
            'status'        => TransferRequest::STATUS_REJECTED,
            'rejected_by'   => $rejectedBy,
            'rejected_at'   => now(),
            'reject_reason' => $reason,
        ]);
    }

    public function cancel(int $id, int $cancelledBy)
    {
        $request = $this->transferRequestRepository->getById($id);

        if ((int) $request->requested_by !== $cancelledBy) {
            throw new \Exception("Hanya pembuat request yang dapat membatalkan.");
        }

        if (! $request->isCancellable()) {
            throw new \Exception("Request tidak dapat dibatalkan karena sudah diproses.");
        }

        return $this->transferRequestRepository->update($id, [
            'status'       => TransferRequest::STATUS_CANCELLED,
            'cancelled_by' => $cancelledBy,
            'cancelled_at' => now(),
        ]);
    }

    private function guardApprover(int $userId): void
    {
        if (! $this->transferRequestRepository->isApprover($userId)) {
            throw new \Exception("Anda tidak memiliki wewenang untuk memproses transfer request.");
        }
    }
}
