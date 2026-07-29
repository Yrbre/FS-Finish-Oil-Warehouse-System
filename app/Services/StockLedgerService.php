<?php

namespace App\Services;

use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use Carbon\Carbon;

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
}
