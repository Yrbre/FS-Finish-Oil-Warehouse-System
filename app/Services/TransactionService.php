<?php

namespace App\Services;

use App\Models\ItemLocation;
use App\Models\StockLedger;
use App\Models\Transaction;
use App\Repositories\Interfaces\ItemRepositoryInterface;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Repositories\Interfaces\WarehouseRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransactionServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TransactionService implements TransactionServiceInterface
{
    public function __construct(
        protected TransactionRepositoryInterface $transactionRepository,
        protected ItemRepositoryInterface $itemRepository,
        protected WarehouseRepositoryInterface $warehouseRepository,
        protected ItemLocationServiceInterface $itemLocationService,
        protected StockLedgerServiceInterface $stockLedgerService,
        protected StockLedgerRepositoryInterface $stockLedgerRepository,
    ) {}

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
        return DB::transaction(fn() => $this->persistTransaction($data, $createdBy));
    }

    public function createBatch(array $entries, int $createdBy): array
    {
        if (empty($entries)) {
            throw new \Exception("Minimal harus ada 1 transaksi yang diinput.");
        }

        return DB::transaction(function () use ($entries, $createdBy) {
            $transactions = [];

            foreach ($entries as $i => $entryData) {
                $entryData['doc_type'] = $entryData['doc_type'] ?? Transaction::DOC_PORC;

                try {
                    $transactions[] = $this->persistTransaction($entryData, $createdBy);
                } catch (\Exception $e) {
                    throw new \Exception("Form ke-" . ($i + 1) . ": " . $e->getMessage());
                }
            }

            return $transactions;
        });
    }

    /* ================= CREATE ================= */

    private function persistTransaction(array $data, int $createdBy)
    {
        $item        = $this->itemRepository->getById((int) $data['item_id']);
        $warehouse   = $this->warehouseRepository->getById((int) $data['warehouse_id']);
        $itemId      = (int) $data['item_id'];
        $warehouseId = (int) $data['warehouse_id'];
        $demanderId  = (int) $data['demander_id'];
        $transDate   = Carbon::parse($data['trans_date']);

        $this->guardZone($data['doc_type'], $warehouse);

        // PORC berbasis package: berat dihitung, bukan diinput.
        // CONS/ADJ berbasis kg: trans_qty dipakai apa adanya.
        $transQty = $data['doc_type'] === Transaction::DOC_PORC
            ? $this->resolvePorcWeight($data)
            : round((float) $data['trans_qty'], 2);

        $data['trans_qty'] = $transQty;

        // bb_qty = stok milik department INI di gudang ini, sebelum
        // transaksi berjalan. Difilter demander_id — stok department
        // lain di gudang yang sama tidak boleh ikut terhitung.
        $bbQty = $this->itemLocationService->getTotalStock($itemId, $warehouseId, $demanderId);

        [$inQty, $outQty] = $this->resolveDirection($data, $transQty);

        if ($outQty > $bbQty) {
            throw new \Exception(
                "Stok tidak mencukupi untuk item {$item->item_no}. Stok tersedia: " .
                    number_format($bbQty, 2, ',', '.') . " kg, dibutuhkan: " .
                    number_format($outQty, 2, ',', '.') . " kg."
            );
        }

        $ebQty = round($bbQty + $inQty - $outQty, 2);

        // receiving_lot digenerate SEKALI di sini, lalu dipakai ulang
        // oleh lot yang dibuat applyStockMovement(). Versi lama
        // memanggil generator dua kali — kebetulan hasilnya sama,
        // tapi rapuh.
        if ($data['doc_type'] === Transaction::DOC_PORC) {
            $data['receiving_lot'] = $this->itemLocationService->generateReceivingLot($transDate);
        }

        $data['demander_id']  = $demanderId;
        $data['item_no']      = $item->item_no;
        $data['item_desc']    = $item->item_desc;
        $data['item_uom']     = $item->item_uom;
        $data['item_glclass'] = $item->item_glclass;
        $data['status']       = 'NEW';
        $data['created_by']   = $createdBy;
        $data['in_qty']       = $inQty;
        $data['out_qty']      = $outQty;
        $data['bb_qty']       = $bbQty;
        $data['eb_qty']       = $ebQty;

        $transaction = $this->transactionRepository->create($data);

        $this->applyStockMovement($transaction, $data, $transDate);

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
    }

    /* ================= EDIT PORC ================= */

    /**
     * Koreksi salah input PORC.
     *
     * Berbeda dari ADJ: ini memperbaiki angka yang memang salah ketik,
     * bukan mencatat selisih fisik. Karena itu ledger PORC-nya
     * DIPERBARUI, bukan ditambah baris ADJ baru — kalau ditambah,
     * kartu stok akan menampilkan selisih yang sebenarnya tidak
     * pernah terjadi.
     *
     * Qty hanya boleh diubah selama lot belum tersentuh mutasi apa pun.
     */
    public function updatePorc(int $id, array $data, int $editedBy)
    {
        return DB::transaction(function () use ($id, $data, $editedBy) {
            $transaction = $this->transactionRepository->getById($id);

            if ($transaction->doc_type !== Transaction::DOC_PORC) {
                throw new \Exception("Hanya transaksi Supply Oil (PORC) yang dapat diedit.");
            }

            $lot = $this->findLotOfPorc($transaction);

            $qtyDiubah = isset($data['qty_package']) || isset($data['qty_perpackage']);

            if ($qtyDiubah && $lot->isTouched()) {
                throw new \Exception(
                    "Qty tidak dapat diubah karena stok lot ini sudah terpakai " .
                        number_format($lot->consumed_weight, 2, ',', '.') . " kg. " .
                        "Gunakan Adjustment untuk mengoreksi selisihnya."
                );
            }

            // --- field non-qty, selalu boleh diubah ---
            $lotUpdate = [];
            $trxUpdate = [
                'edited_at'   => now(),
                'edited_by'   => $editedBy,
                'edit_reason' => $data['edit_reason'] ?? null,
            ];

            foreach (['vendor_lot', 'package', 'production_date'] as $field) {
                if (array_key_exists($field, $data)) {
                    $lotUpdate[$field] = $data[$field];
                    $trxUpdate[$field] = $data[$field];
                }
            }

            if (array_key_exists('notes', $data)) {
                $trxUpdate['notes'] = $data['notes'];
            }

            if (array_key_exists('exp_date', $data)) {
                $lotUpdate['exp_date'] = $data['exp_date'];
                $trxUpdate['exp_date'] = $data['exp_date'];
            }

            // --- qty, hanya kalau lot masih utuh ---
            if ($qtyDiubah) {
                $perPackage = (float) ($data['qty_perpackage'] ?? $transaction->qty_perpackage);
                $package    = (float) ($data['qty_package'] ?? $transaction->qty_package);

                if ($perPackage <= 0 || $package <= 0) {
                    throw new \Exception("Ukuran dan jumlah package harus lebih dari 0.");
                }

                $newWeight = round($perPackage * $package, 2);

                $lotUpdate['qty_perpackage'] = $perPackage;
                $lotUpdate['qty_package']    = $package;
                $lotUpdate['qty_weight']     = $newWeight;
                // Lot dianggap seolah baru dibuat dengan angka terkoreksi.
                $lotUpdate['initial_weight'] = $newWeight;

                $trxUpdate['qty_perpackage'] = $perPackage;
                $trxUpdate['qty_package']    = $package;
                $trxUpdate['trans_qty']      = $newWeight;
                $trxUpdate['in_qty']         = $newWeight;
                $trxUpdate['eb_qty']         = round((float) $transaction->bb_qty + $newWeight, 2);
            }

            if (! empty($lotUpdate)) {
                $this->itemLocationService->update($lot->id, $lotUpdate);
            }

            $this->transactionRepository->update($id, $trxUpdate);

            // Ledger diperbarui, bukan ditambah — ini koreksi input,
            // bukan mutasi baru.
            if ($qtyDiubah) {
                $this->stockLedgerRepository->updateByRef(
                    StockLedger::REF_TRANSACTION,
                    $id,
                    [
                        'in_qty' => $trxUpdate['in_qty'],
                        'eb_qty' => $trxUpdate['eb_qty'],
                    ]
                );
            }

            return $this->transactionRepository->getById($id);
        });
    }

    /* ================= DELETE ================= */

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

            $lot = $this->findLotOfPorc($transaction);

            if ($lot->isTouched()) {
                throw new \Exception(
                    "Tidak dapat menghapus transaksi ini karena sebagian stoknya sudah terpakai."
                );
            }

            $this->itemLocationService->delete($lot->id);
            $this->transactionRepository->delete($id);
            $this->stockLedgerRepository->deleteByRef(StockLedger::REF_TRANSACTION, $id);

            return true;
        });
    }

    /* ================= PRIVATE ================= */

    /**
     * CONS dan ADJ dilarang di gudang IMC.
     *
     * Di IMC package harus selalu utuh — itulah yang menjamin FEFO
     * transfer selalu bisa mengirim package penuh. Kalau CONS
     * diizinkan di sana, akan muncul package terbuka dan jaminan
     * itu runtuh.
     */
    private function guardZone(string $docType, $warehouse): void
    {
        $isImc = $warehouse->isImc();

        if ($docType === Transaction::DOC_PORC && ! $isImc) {
            throw new \Exception(
                "Supply Oil hanya dapat diterima di gudang IMC. " .
                    "Gudang {$warehouse->name} bukan gudang IMC."
            );
        }

        if (in_array($docType, [Transaction::DOC_CONS, Transaction::DOC_ADJ], true) && $isImc) {
            $label = $docType === Transaction::DOC_CONS ? 'Pemakaian' : 'Adjustment';

            throw new \Exception(
                "{$label} tidak dapat dilakukan di gudang IMC. " .
                    "Ajukan Transfer Request untuk memindahkan barang ke gudang department terlebih dahulu."
            );
        }
    }

    /**
     * Berat PORC = jumlah package x ukuran per package.
     * Operator tidak menginput berat langsung.
     */
    private function resolvePorcWeight(array $data): float
    {
        $perPackage = (float) ($data['qty_perpackage'] ?? 0);
        $package    = (float) ($data['qty_package'] ?? 0);

        if ($perPackage <= 0) {
            throw new \Exception("Ukuran per package harus lebih dari 0.");
        }

        if ($package <= 0) {
            throw new \Exception("Jumlah package harus lebih dari 0.");
        }

        return round($perPackage * $package, 2);
    }

    /**
     * Versi lama diam-diam menganggap adj_type yang tidak dikenali
     * sebagai OUT — artinya salah ketik akan mengurangi stok tanpa
     * peringatan.
     */
    private function resolveDirection(array $data, float $transQty): array
    {
        switch ($data['doc_type']) {
            case Transaction::DOC_PORC:
                return [$transQty, 0.0];

            case Transaction::DOC_CONS:
            case Transaction::DOC_DISPOSAL:
                return [0.0, $transQty];

            case Transaction::DOC_ADJ:
                $adjType = $data['adj_type'] ?? null;

                if ($adjType === Transaction::ADJ_IN) {
                    return [$transQty, 0.0];
                }

                if ($adjType === Transaction::ADJ_OUT) {
                    return [0.0, $transQty];
                }

                throw new \Exception("Jenis adjustment harus dipilih (penambahan atau pengurangan).");

            default:
                throw new \Exception("Jenis transaksi tidak dikenali: " . $data['doc_type']);
        }
    }

    private function applyStockMovement(Transaction $transaction, array $data, Carbon $transDate): void
    {
        switch ($transaction->doc_type) {
            case Transaction::DOC_PORC:
                $this->itemLocationService->addLot([
                    'item_id'         => $transaction->item_id,
                    'warehouse_id'    => $transaction->warehouse_id,
                    'demander_id'     => $transaction->demander_id,
                    'vendor_lot'      => $data['vendor_lot'] ?? null,
                    // Pakai nomor yang sudah digenerate, jangan generate lagi.
                    'receiving_lot'   => $transaction->receiving_lot,
                    'production_date' => $data['production_date'] ?? null,
                    'exp_date'        => $data['exp_date'] ?? null,
                    'qty_perpackage'  => $data['qty_perpackage'],
                    'qty_package'     => $data['qty_package'],
                    'package'         => $data['package'] ?? null,
                    'received_date'   => $transDate->toDateString(),
                    'is_warehouse_stock' => true,
                ]);
                break;

            case Transaction::DOC_CONS:
                $result = $this->itemLocationService->allocateForCons(
                    (int) $transaction->item_id,
                    (int) $transaction->warehouse_id,
                    (int) $transaction->demander_id,
                    (float) $transaction->out_qty
                );

                if (! $result->isFulfilled()) {
                    throw new \Exception(
                        "Stok tidak mencukupi. Kurang " .
                            number_format($result->shortage, 2, ',', '.') . " kg."
                    );
                }

                foreach ($result->lines as $line) {
                    $this->itemLocationService->deductLot($line->lot->id, $line->qtyTaken);
                }
                break;

            case Transaction::DOC_ADJ:
                if (empty($data['item_location_id'])) {
                    throw new \Exception("Adjustment harus memilih lot yang akan dikoreksi.");
                }

                $lot = $this->itemLocationService->getById((int) $data['item_location_id']);

                if ((int) $lot->demander_id !== (int) $transaction->demander_id) {
                    throw new \Exception("Lot yang dipilih bukan milik department ini.");
                }

                $newWeight = round(
                    (float) $lot->qty_weight
                        + (float) $transaction->in_qty
                        - (float) $transaction->out_qty,
                    2
                );

                if ($newWeight < 0) {
                    throw new \Exception(
                        "Adjustment membuat stok lot menjadi minus. Stok lot saat ini: " .
                            number_format((float) $lot->qty_weight, 2, ',', '.') . " kg."
                    );
                }

                $this->itemLocationService->update($lot->id, ['qty_weight' => $newWeight]);
                break;
        }
    }

    /**
     * Cari lot yang dibuat oleh sebuah transaksi PORC.
     *
     * Versi lama mencari pakai vendor_lot + received_date — dua PORC
     * di hari sama dengan vendor_lot sama (atau dua-duanya null)
     * akan mengambil lot acak. receiving_lot unik per penerimaan,
     * jadi pasti tepat.
     */
    private function findLotOfPorc(Transaction $transaction): ItemLocation
    {
        $lot = ItemLocation::where('receiving_lot', $transaction->receiving_lot)
            ->where('item_id', $transaction->item_id)
            ->where('warehouse_id', $transaction->warehouse_id)
            ->first();

        if (! $lot) {
            throw new \Exception("Lot dari transaksi ini sudah tidak ditemukan.");
        }

        return $lot;
    }
}
