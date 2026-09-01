<?php

namespace App\Services;

use App\Models\Department;
use App\Models\Item;
use App\Models\ItemLocation;
use App\Models\User;
use App\Notifications\MinimumStockAlert;
use App\Notifications\NearExpiryAlert;
use Illuminate\Support\Facades\DB;

class StockAlertService
{
    /**
     * Cek semua item yang punya min_stock, per department pemilik.
     *
     * Stok dihitung dari kepemilikan (demander_id), bukan lokasi —
     * jadi barang yang masih dititipkan di gudang IMC tetap dihitung
     * sebagai milik department tersebut.
     *
     * @return int jumlah notifikasi yang dikirim
     */
    public function checkMinimumStock(): int
    {
        // Item dipantau kalau punya min_stock global ATAU punya
        // pengaturan khusus per department.
        $items = Item::with('minimumStocks')
            ->where(function ($q) {
                $q->where('min_stock', '>', 0)
                    ->orWhereHas('minimumStocks', fn($q2) => $q2->where('is_active', true));
            })
            ->get();

        if ($items->isEmpty()) {
            return 0;
        }

        $imcWarehouseIds = $this->imcWarehouseIds();
        $sent = 0;

        foreach ($items as $item) {
            $owners = ItemLocation::where('item_id', $item->id)
                ->whereNull('deleted_at')
                ->select('demander_id')
                ->distinct()
                ->pluck('demander_id');

            foreach ($owners as $demanderId) {
                if (! $demanderId) {
                    continue;
                }

                $ambang = $item->minStockFor((int) $demanderId);

                // Null = department ini tidak memantau item tersebut.
                if ($ambang === null || $ambang <= 0) {
                    continue;
                }

                $total = (float) ItemLocation::where('item_id', $item->id)
                    ->where('demander_id', $demanderId)
                    ->available()
                    ->sum('qty_weight');

                if ($total >= $ambang) {
                    continue;
                }

                $local = (float) ItemLocation::where('item_id', $item->id)
                    ->where('demander_id', $demanderId)
                    ->whereNotIn('warehouse_id', $imcWarehouseIds)
                    ->available()
                    ->sum('qty_weight');

                $department = Department::find($demanderId);

                if (! $department) {
                    continue;
                }

                foreach ($this->departmentUsers((int) $demanderId) as $user) {
                    $user->notify(new MinimumStockAlert($item, $department, $total, $local, $ambang));
                    $sent++;
                }
            }
        }

        return $sent;
    }

    /**
     * Peringatan 3, 2, dan 1 bulan menjelang kedaluwarsa.
     *
     * Tiap lot hanya diperingatkan sekali per tingkat — ditandai
     * lewat expiry_alerted_level supaya scheduler harian tidak
     * mengirim notifikasi yang sama berulang kali.
     *
     * @return int jumlah notifikasi yang dikirim
     */
    public function checkNearExpiry(): int
    {
        $levels = config('notification.expiry_alert_months', [3, 2, 1]);
        sort($levels); // dari yang paling jauh, supaya level turun bertahap

        $sent = 0;

        foreach ($levels as $months) {
            $batas = now()->addMonths($months)->endOfDay();

            $lots = ItemLocation::with(['item', 'warehouse', 'demander'])
                ->available()
                ->whereNotNull('exp_date')
                ->where('exp_date', '<=', $batas)
                // Belum pernah diperingatkan, atau baru pada tingkat
                // yang lebih longgar (mis. sudah 3 bulan, kini masuk 2).
                ->where(function ($q) use ($months) {
                    $q->whereNull('expiry_alerted_level')
                        ->orWhere('expiry_alerted_level', '>', $months);
                })
                ->get();

            foreach ($lots as $lot) {
                if (! $lot->demander_id) {
                    continue;
                }

                $users = $this->departmentUsers((int) $lot->demander_id);

                foreach ($users as $user) {
                    $user->notify(new NearExpiryAlert($lot, $months));
                    $sent++;
                }

                $lot->update(['expiry_alerted_level' => $months]);
            }
        }

        return $sent;
    }

    /** Semua user aktif di department pemilik stok. */
    private function departmentUsers(int $departmentId)
    {
        return User::where('department_id', $departmentId)->get();
    }

    private function imcWarehouseIds(): array
    {
        return DB::table('warehouses')
            ->join('departments', 'departments.id', '=', 'warehouses.department_id')
            ->where('departments.code', Department::CODE_IMC)
            ->whereNull('warehouses.deleted_at')
            ->pluck('warehouses.id')
            ->all();
    }
}
