<?php

namespace App\Console\Commands;

use App\Services\StockAlertService;
use Illuminate\Console\Command;

class CheckStockAlerts extends Command
{
    protected $signature = 'stock:check-alerts
                            {--min : Hanya cek minimum stock}
                            {--expiry : Hanya cek mendekati kedaluwarsa}';

    protected $description = 'Kirim notifikasi stok minimum dan lot yang mendekati kedaluwarsa';

    public function handle(StockAlertService $service): int
    {
        $onlyMin    = $this->option('min');
        $onlyExpiry = $this->option('expiry');
        $runAll     = ! $onlyMin && ! $onlyExpiry;

        if ($runAll || $onlyMin) {
            $this->info('Memeriksa stok minimum...');
            $count = $service->checkMinimumStock();
            $this->line("  {$count} notifikasi terkirim.");
        }

        if ($runAll || $onlyExpiry) {
            $this->info('Memeriksa lot yang mendekati kedaluwarsa...');
            $count = $service->checkNearExpiry();
            $this->line("  {$count} notifikasi terkirim.");
        }

        return self::SUCCESS;
    }
}
