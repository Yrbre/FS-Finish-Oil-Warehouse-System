<?php

namespace App\Services;

use App\Models\ItemLocation;
use App\Models\StockLedger;
use App\Models\Transaction;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    protected TransactionRepositoryInterface $transactionRepository;
    protected ItemRepositoryInterface $itemRepository;
    protected ItemLocationServiceInterface $itemLocationService;
    protected StockLedgerServiceInterface $stockLedgerService;
    protected \App\Repositories\Interfaces\StockLedgerRepositoryInterface $stockLedgerRepository;

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        ItemRepositoryInterface $itemRepository,
        ItemLocationServiceInterface $itemLocationService,
        StockLedgerServiceInterface $stockLedgerService,
        \App\Repositories\Interfaces\StockLedgerRepositoryInterface $stockLedgerRepository
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->itemRepository        = $itemRepository;
        $this->itemLocationService   = $itemLocationService;
        $this->stockLedgerService    = $stockLedgerService;
        $this->stockLedgerRepository = $stockLedgerRepository;
    }

    public function getAll()
    {
        return $this->transactionRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->transactionRepository->getById($id);
    }

    public function create(array $data, int $createdBy)
    {
        return DB::transaction(function () use ($data, $createdBy) {
            $item      = $this->itemRepository->getById((int) $data['item_id']);
            $itemId    = (int) $data['item_id'];
            $warehouseId = (int) $data['warehouse_id'];
            $transDate = Carbon::parse($data['trans_date']);
            $transQty  = (float) $data['trans_qty'];
            $data['receiving_lot'] = $this->itemLocationService->generateReceivingLot($transDate);
            $data['exp_date'] = $this->applyExpiryRule($data);
            // bb_qty = kondisi stok SEKARANG di item_locations, bukan dari
            // riwayat ledger. Ini "saldo ATM", diambil saat transaksi dibuat,
            // apapun tanggal transaksinya (termasuk kalau backdate).
            $bbQty = $this->itemLocationService->getTotalStock($itemId, $warehouseId);

            $data['item_no']   = $item->item_no;
            $data['item_desc'] = $item->item_desc;
            $data['item_uom']  = $item->item_uom;
            $data['status']     = 'NEW';
            $data['created_by'] = $createdBy;

            [$inQty, $outQty] = $this->resolveDirection($data, $transQty);
            $data['in_qty']  = $inQty;
            $data['out_qty'] = $outQty;

            // Validasi stok tidak minus — cek terhadap kondisi SEKARANG
            if ($outQty > $bbQty) {
                throw new \Exception(
                    "Stok tidak mencukupi. Stok tersedia: " . number_format($bbQty, 2, ',', '.') .
                        ", dibutuhkan: " . number_format($outQty, 2, ',', '.')
                );
            }

            $ebQty = $bbQty + $inQty - $outQty;

            $data['bb_qty'] = $bbQty;
            $data['eb_qty'] = $ebQty;

            $transaction = $this->transactionRepository->create($data);

            // Terapkan perubahan fisik ke item_locations
            $this->applyStockMovement($transaction, $data, $transDate);

            // Arsip ke ledger — murni catatan riwayat, tidak ada recalculate
            $this->stockLedgerService->record([
                'item_id'      => $itemId,
                'warehouse_id' => $warehouseId,
                'trans_date'   => $transDate->toDateString(),
                'in_qty'       => $inQty,
                'out_qty'      => $outQty,
                'bb_qty'       => $bbQty,
                'eb_qty'       => $ebQty,
                'doc_type'     => $transaction->doc_type,
                'ref_type'     => StockLedger::REF_TRANSACTION,
                'ref_id'       => $transaction->id,
            ]);

            return $transaction->fresh();
        });
    }

    public function delete(int $id)
    {
        return DB::transaction(function () use ($id) {
            $transaction = $this->transactionRepository->getById($id);

            if ($transaction->doc_type !== Transaction::DOC_PORC) {
                throw new \Exception(
                    "Transaksi {$transaction->doc_type} tidak dapat dihapus. " .
                        "Gunakan Adjustment (ADJ) untuk mengoreksi nilainya."
                );
            }

            $this->revertPorcLot($transaction);

            $this->transactionRepository->delete($id);

            // Hapus arsip ledger-nya juga — tidak ada recalculate karena
            // bb_qty/eb_qty transaksi lain sudah final saat dibuat,
            // tidak saling bergantung satu sama lain.
            $this->stockLedgerRepository->deleteByRef(StockLedger::REF_TRANSACTION, $id);

            return true;
        });
    }

    private function resolveDirection(array $data, float $transQty): array
    {
        switch ($data['doc_type']) {
            case Transaction::DOC_PORC:
                return [$transQty, 0];

            case Transaction::DOC_CONS:
                return [0, $transQty];

            case Transaction::DOC_ADJ:
                return ($data['adj_type'] ?? null) === Transaction::ADJ_IN
                    ? [$transQty, 0]
                    : [0, $transQty];

            default:
                throw new \Exception("Jenis transaksi tidak dikenali: " . $data['doc_type']);
        }
    }

    /**
     * Terapkan perubahan ke item_locations — inilah "sumber kebenaran" stok.
     */
    private function applyStockMovement(Transaction $transaction, array $data, \DateTime $transDate): void
    {
        switch ($transaction->doc_type) {
            case Transaction::DOC_PORC:
                $this->itemLocationService->create([
                    'item_id'            => $transaction->item_id,
                    'warehouse_id'       => $transaction->warehouse_id,
                    'vendor_lot'         => $data['vendor_lot'] ?? null,
                    'receiving_lot'      => $this->itemLocationService->generateReceivingLot($transDate),
                    'production_date'    => $data['production_date'] ?? null,
                    'qty_weight'         => $transaction->in_qty,
                    'qty_unit'           => $data['qty_unit'] ?? null,
                    'package'            => $data['package'] ?? null,
                    'received_date'      => $transaction->trans_date,
                    'is_warehouse_stock' => true,
                ]);
                break;

            case Transaction::DOC_CONS:
                $allocation = $this->itemLocationService->allocateFefo(
                    (int) $transaction->item_id,
                    (int) $transaction->warehouse_id,
                    (float) $transaction->out_qty
                );

                foreach ($allocation as $row) {
                    $this->itemLocationService->deductLot(
                        $row['item_location']->id,
                        $row['qty_to_take']
                    );
                }
                break;

            case Transaction::DOC_ADJ:
                if (empty($data['item_location_id'])) {
                    throw new \Exception("Adjustment harus memilih lot yang akan dikoreksi.");
                }

                $lot = $this->itemLocationService->getById((int) $data['item_location_id']);

                $newQty = (float) $lot->qty_weight
                    + (float) $transaction->in_qty
                    - (float) $transaction->out_qty;

                if ($newQty < 0) {
                    throw new \Exception(
                        "Adjustment membuat stok lot menjadi minus. Stok lot saat ini: " .
                            number_format((float) $lot->qty_weight, 2, ',', '.')
                    );
                }

                $this->itemLocationService->update($lot->id, ['qty_weight' => $newQty]);
                break;
        }
    }

    private function revertPorcLot(Transaction $transaction): void
    {
        $lot = ItemLocation::where('item_id', $transaction->item_id)
            ->where('warehouse_id', $transaction->warehouse_id)
            ->where('vendor_lot', $transaction->vendor_lot)
            ->where('received_date', $transaction->trans_date)
            ->first();

        if (! $lot) {
            throw new \Exception("Lot dari transaksi ini sudah tidak ditemukan, tidak dapat dihapus.");
        }

        if ((float) $lot->qty_weight < (float) $transaction->in_qty) {
            throw new \Exception(
                "Tidak dapat menghapus transaksi ini karena sebagian stoknya sudah terpakai."
            );
        }

        $remaining = (float) $lot->qty_weight - (float) $transaction->in_qty;

        if ($remaining > 0) {
            $this->itemLocationService->update($lot->id, ['qty_weight' => $remaining]);
        } else {
            $this->itemLocationService->delete($lot->id);
        }
    }

    private function applyExpiryRule(array $data): string | null
    {
        if (! empty($data['production_date'])) {
            $data['exp_date'] = \Carbon\Carbon::parse($data['production_date'])
                ->addYear()
                ->toDateString();
        }

        return $data['exp_date'] ?? null;
    }
}
