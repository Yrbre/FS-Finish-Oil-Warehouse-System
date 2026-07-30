<?php

namespace App\Services;

use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class StockLedgerService implements StockLedgerServiceInterface
{
    protected StockLedgerRepositoryInterface $stockLedgerRepository;

    public function __construct(StockLedgerRepositoryInterface $stockLedgerRepository)
    {
        $this->stockLedgerRepository = $stockLedgerRepository;
    }

    public function record(array $data)
    {
        return $this->stockLedgerRepository->create($data);
    }

    public function getMonthlyStockCard(int $itemId, int $month, int $year, ?array $warehouseIds = null)
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        $runningBalance = $this->stockLedgerRepository
            ->getBalanceBefore($itemId, $warehouseIds, $startDate);

        $mutations = $this->stockLedgerRepository
            ->getDailyMutation($itemId, $startDate, $endDate, $warehouseIds);

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

            $runningBalance = $ebQty;
        }

        return $stockCard;
    }

    public function getStaffMonthlyStockCard(int $itemId, int $month, int $year, array $warehouseIds)
    {
        [$startDate, $endDate] = $this->monthRange($month, $year);

        $openingBalance = $this->stockLedgerRepository
            ->getStaffBalanceBefore($itemId, $warehouseIds, $startDate);

        $mutations = $this->stockLedgerRepository
            ->getStaffDailyMutation($itemId, $startDate, $endDate, $warehouseIds);

        return $this->buildStockCard($startDate, $endDate, $openingBalance, $mutations);
    }

    private function monthRange(int $month, int $year): array
    {
        $startDate = Carbon::create($year, $month, 1)->startOfDay();
        $endDate   = $startDate->copy()->endOfMonth();

        return [$startDate, $endDate];
    }

    /**
     * Susun kartu stok harian dari saldo awal + mutasi yang sudah
     * diagregasi. Dipakai bersama oleh versi lengkap maupun versi staff —
     * bedanya cuma sumber data mutasinya, bukan cara menyusun tabelnya.
     */
    private function buildStockCard(Carbon $startDate, Carbon $endDate, float $openingBalance, Collection $mutations)
    {
        $runningBalance = $openingBalance;
        $stockCard      = collect();

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

            $runningBalance = $ebQty;
        }

        return $stockCard;
    }
}
