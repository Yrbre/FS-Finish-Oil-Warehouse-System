<?php

namespace App\Services;

use App\Models\StockLedger;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Repositories\Interfaces\TransactionRepositoryInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use Carbon\Carbon;

class StockLedgerService implements StockLedgerServiceInterface
{
    protected StockLedgerRepositoryInterface $stockLedgerRepository;
    protected TransactionRepositoryInterface $transactionRepository;

    public function __construct(
        StockLedgerRepositoryInterface $stockLedgerRepository,
        TransactionRepositoryInterface $transactionRepository
    ) {
        $this->stockLedgerRepository = $stockLedgerRepository;
        $this->transactionRepository = $transactionRepository;
    }

    public function record(array $data)
    {
        // bb_qty & eb_qty diisi 0 dulu — nilai sebenarnya ditentukan
        // oleh recalculateFrom() supaya urutan kronologis tetap benar
        // walaupun transaksinya backdate.
        $ledger = $this->stockLedgerRepository->create(array_merge($data, [
            'bb_qty' => 0,
            'eb_qty' => 0,
        ]));

        $this->recalculateFrom(
            (int) $data['item_id'],
            (int) $data['warehouse_id'],
            Carbon::parse($data['trans_date'])
        );

        return $ledger->fresh();
    }

    public function recalculateFrom(int $itemId, int $warehouseId, Carbon $fromDate): void
    {
        // Titik awal = akumulasi seluruh mutasi sebelum tanggal ini
        $runningBalance = $this->stockLedgerRepository
            ->getBalanceBefore($itemId, $warehouseId, $fromDate);

        $entries = $this->stockLedgerRepository
            ->getFromDate($itemId, $warehouseId, $fromDate);

        foreach ($entries as $entry) {
            $bbQty = $runningBalance;
            $ebQty = $bbQty + (float) $entry->in_qty - (float) $entry->out_qty;

            // Aturan wajib: stok tidak boleh minus di titik waktu manapun
            if ($ebQty < 0) {
                throw new \Exception(
                    "Stok tidak mencukupi pada tanggal " . $entry->trans_date->format('d-m-Y') . ". " .
                        "Saldo tersedia: " . number_format($bbQty, 2, ',', '.') . ", " .
                        "dibutuhkan: " . number_format((float) $entry->out_qty, 2, ',', '.') . "."
                );
            }

            $this->stockLedgerRepository->updateBalance($entry->id, $bbQty, $ebQty);

            // Sinkronkan balik ke tabel transactions supaya daftar transaksi
            // menampilkan saldo yang sudah dikoreksi
            if ($entry->ref_type === StockLedger::REF_TRANSACTION) {
                $this->transactionRepository->syncBalance((int) $entry->ref_id, $bbQty, $ebQty);
            }

            $runningBalance = $ebQty;
        }
    }

    public function removeByRef(string $refType, int $refId, int $itemId, int $warehouseId, Carbon $fromDate): void
    {
        $this->stockLedgerRepository->deleteByRef($refType, $refId);
        $this->recalculateFrom($itemId, $warehouseId, $fromDate);
    }

    public function getMonthlyStockCard(int $itemId, int $month, int $year, ?int $warehouseId = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        // Saldo awal bulan
        $runningBalance = $warehouseId
            ? $this->stockLedgerRepository->getBalanceBefore($itemId, $warehouseId, $startDate)
            : $this->stockLedgerRepository->getBalanceBeforeAllWarehouses($itemId, $startDate);

        $mutations = $this->stockLedgerRepository
            ->getDailyMutation($itemId, $startDate, $endDate, $warehouseId);

        $stockCard = collect();

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $key = $date->format('Y-m-d');

            $inQty    = 0.0;
            $outQty   = 0.0;
            $adjQty   = 0.0;
            $docTypes = null;

            if ($mutations->has($key)) {
                $row = $mutations->get($key);

                $inQty    = (float) $row->in_qty;
                $outQty   = (float) $row->out_qty;
                $adjQty   = (float) $row->adj_qty;
                $docTypes = $row->doc_types;
            }

            $bbQty = $runningBalance;
            $ebQty = $bbQty + $inQty - $outQty + $adjQty;

            $stockCard->push((object) [
                'trans_date' => $key,
                'bb_qty'     => $bbQty,
                'in_qty'     => $inQty,
                'out_qty'    => $outQty,
                'adj_qty'    => $adjQty,
                'eb_qty'     => $ebQty,
                'doc_type'   => $docTypes,
                'has_trx'    => $mutations->has($key),
            ]);

            // Saldo akhir hari ini jadi saldo awal hari berikutnya
            $runningBalance = $ebQty;
        }

        return $stockCard;
    }
}
