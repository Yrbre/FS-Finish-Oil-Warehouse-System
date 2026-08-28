<?php

namespace App\Services;

use App\Models\StockLedger;
use App\Models\Transaction;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use Illuminate\Support\Facades\DB;

class DisposalService
{
    public function __construct(
        protected ItemLocationRepositoryInterface $itemLocationRepository,
        protected ItemLocationServiceInterface $itemLocationService,
        protected TransactionRepositoryInterface $transactionRepository,
        protected StockLedgerServiceInterface $stockLedgerService,
    ) {}

    /**
     * Buang satu lot: stok dinolkan, transaksi DISPOSAL dicatat,
     * ledger diperbarui.
     *
     * Seluruh lot dibuang sekaligus — kalau hanya sebagian yang
     * rusak, gunakan Adjustment untuk mengurangi lebih dulu.
     */
    public function dispose(int $itemLocationId, int $disposedBy, string $reason)
    {
        return DB::transaction(function () use ($itemLocationId, $disposedBy, $reason) {
            $lot = $this->itemLocationRepository->getByIdForUpdate($itemLocationId);

            $qty = (float) $lot->qty_weight;

            // bb_qty = stok milik department ini di gudang ini,
            // sebelum lot dibuang.
            $bbQty = $this->itemLocationService->getTotalStock(
                (int) $lot->item_id,
                (int) $lot->warehouse_id,
                (int) $lot->demander_id
            );

            $transaction = $this->transactionRepository->create([
                'item_id'          => $lot->item_id,
                'warehouse_id'     => $lot->warehouse_id,
                'demander_id'      => $lot->demander_id,
                'item_location_id' => $lot->id,
                'doc_type'         => Transaction::DOC_DISPOSAL,
                'trans_date'       => now()->toDateString(),
                'trans_qty'        => $qty,
                'in_qty'           => 0,
                'out_qty'          => $qty,
                'bb_qty'           => $bbQty,
                'eb_qty'           => round($bbQty - $qty, 2),
                'vendor_lot'       => $lot->vendor_lot,
                'receiving_lot'    => $lot->receiving_lot,
                'exp_date'         => $lot->exp_date?->toDateString(),
                'package'          => $lot->package,
                'qty_perpackage'   => $lot->qty_perpackage,
                'qty_package'      => $lot->qty_package_display,
                'item_no'          => $lot->item->item_no,
                'item_desc'        => $lot->item->item_desc,
                'item_uom'         => $lot->item->item_uom,
                'item_glclass'     => $lot->item->item_glclass,
                'notes'            => $reason,
                'status'           => 'NEW',
                'created_by'       => $disposedBy,
            ]);

            $this->itemLocationService->disposeLot($itemLocationId, $disposedBy, $reason);

            $this->stockLedgerService->record([
                'item_id'      => $lot->item_id,
                'warehouse_id' => $lot->warehouse_id,
                'trans_date'   => now()->toDateString(),
                'in_qty'       => 0,
                'out_qty'      => $qty,
                'bb_qty'       => $bbQty,
                'eb_qty'       => round($bbQty - $qty, 2),
                'doc_type'     => StockLedger::DOC_DISPOSAL,
                'ref_type'     => StockLedger::REF_TRANSACTION,
                'ref_id'       => $transaction->id,
            ]);

            return $transaction;
        });
    }
}
