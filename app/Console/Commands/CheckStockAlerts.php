<?php

namespace App\Console\Commands;

use App\Services\StockAlertService;
use Illuminate\Console\Command;

class CheckStockAlerts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'stock:check-alerts
                            {--min : Hanya cek minimum stock}
                            {--expiry : Hanya cek mendekati kedaluwarsa}';

    /**
     * The description of the console command.
     *
     * @var string
     */
    protected $description = 'Kirim notifikasi stok minimum dan lot yang mendekati kedaluwarsa';

    public function handle(StockAlertService $service): int
    {
        $onlyMin    = $this->option('min');
        $onlyExpiry = $this->option('expiry');
        $runAll     = ! $onlyMin && ! $onlyExpiry;

        if ($runAll || $onlyMin) {
            $time = now()->format('Y-m-d H:i:s');
            $this->info("Memeriksa stok minimum - ({$time})");
            $count = $service->checkMinimumStock();
            $this->line("  {$count} notifikasi terkirim.");
        }

        if ($runAll || $onlyExpiry) {
            $time = now()->format('Y-m-d H:i:s');
            $this->info("Memeriksa lot yang mendekati kedaluwarsa - ({$time})");
            $count = $service->checkNearExpiry();
            $this->line("  {$count} notifikasi terkirim.");
        }

        return self::SUCCESS;
    }
}
