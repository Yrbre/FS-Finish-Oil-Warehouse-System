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

    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        ItemRepositoryInterface $itemRepository,
        ItemLocationServiceInterface $itemLocationService,
        StockLedgerServiceInterface $stockLedgerService
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->itemRepository        = $itemRepository;
        $this->itemLocationService   = $itemLocationService;
        $this->stockLedgerService    = $stockLedgerService;
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
            $transDate = Carbon::parse($data['trans_date']);
            $transQty  = (float) $data['trans_qty'];

            // Snapshot info item pada saat transaksi dibuat, supaya
            // riwayat tetap terbaca walaupun master item diubah nanti
            $data['item_no']   = $item->item_no;
            $data['item_desc'] = $item->item_desc;
            $data['item_uom']  = $item->item_uom;

            $data['status']     = 'NEW';
            $data['created_by'] = $createdBy;

            // Tentukan arah mutasi
            [$inQty, $outQty] = $this->resolveDirection($data, $transQty);

            $data['in_qty']  = $inQty;
            $data['out_qty'] = $outQty;

            // Saldo diisi oleh recalculate di stock_ledger
            $data['bb_qty'] = 0;
            $data['eb_qty'] = 0;

            $transaction = $this->transactionRepository->create($data);

            // Terapkan perubahan stok fisik
            $this->applyStockMovement($transaction, $data);

            // Catat ke ledger + recalculate.
            // Kalau ada saldo minus, exception dilempar di sini dan
            // seluruh DB::transaction ini di-rollback.
            $this->stockLedgerService->record([
                'item_id'      => $transaction->item_id,
                'warehouse_id' => $transaction->warehouse_id,
                'trans_date'   => $transDate->toDateString(),
                'in_qty'       => $transaction->in_qty,
                'out_qty'      => $transaction->out_qty,
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

            $itemId      = (int) $transaction->item_id;
            $warehouseId = (int) $transaction->warehouse_id;
            $transDate   = Carbon::parse($transaction->trans_date);

            $this->revertPorcLot($transaction);

            $this->transactionRepository->delete($id);

            $this->stockLedgerService->removeByRef(
                StockLedger::REF_TRANSACTION,
                $id,
                $itemId,
                $warehouseId,
                $transDate
            );

            return true;
        });
    }

    /**
     * Tentukan in_qty / out_qty berdasarkan jenis transaksi.
     */
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
     * Terapkan perubahan stok ke item_locations.
     *
     * PORC    → buat lot baru di warehouse
     * CONS    → ambil FEFO, bisa memecah beberapa lot
     * ADJ in  → tambah ke lot yang dipilih user
     * ADJ out → kurangi dari lot yang dipilih user
     */
    private function applyStockMovement(Transaction $transaction, array $data): void
    {
        switch ($transaction->doc_type) {
            case Transaction::DOC_PORC:
                $this->itemLocationService->create([
                    'item_id'            => $transaction->item_id,
                    'warehouse_id'       => $transaction->warehouse_id,
                    'vendor_lot'         => $data['vendor_lot'] ?? null,
                    'production_date'    => $data['production_date'] ?? null,
                    'exp_date'           => $data['exp_date'] ?? null,
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
                // Adjustment selalu menunjuk lot spesifik, karena sifatnya
                // mengoreksi kesalahan input pada lot tertentu
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

    /**
     * Kembalikan lot yang dibuat oleh transaksi PORC saat transaksi dihapus.
     * Hanya boleh kalau stoknya masih utuh (belum terpakai transaksi lain).
     */
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
}
