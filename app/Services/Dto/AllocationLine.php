<?php

namespace App\Services\Dto;

use App\Models\ItemLocation;

/**
 * Satu baris alokasi dari sebuah lot.
 *
 * Dibuat readonly supaya hasil alokasi tidak bisa diubah setelah
 * dihitung — kalau perlu alokasi berbeda, hitung ulang dari awal.
 */
class AllocationLine
{
    public function __construct(
        public readonly ItemLocation $lot,
        public readonly float $packageTaken,
        public readonly float $qtyTaken,
    ) {}

    /**
     * Bentuk array untuk disimpan ke transfer_request_details.
     */
    public function toDetailArray(int $transferRequestItemId): array
    {
        // Sisa lot setelah baris ini diambil. $this->lot masih memuat
        // nilai SEBELUM deductLot() dijalankan, jadi dikurangi manual.
        $remainingWeight = round((float) $this->lot->qty_weight - $this->qtyTaken, 2);
        $perPackage      = (float) $this->lot->qty_perpackage;

        return [
            'transfer_request_item_id' => $transferRequestItemId,
            'item_location_id'         => $this->lot->id,
            'source_warehouse_id' => $this->lot->warehouse_id,
            'vendor_lot'          => $this->lot->vendor_lot,
            'receiving_lot'       => $this->lot->receiving_lot,
            'exp_date'            => $this->lot->exp_date?->toDateString(),
            'production_date'     => $this->lot->production_date?->toDateString(),
            'package'             => $this->lot->package,
            'qty_perpackage'      => $perPackage,
            'package_taken'       => $this->packageTaken,
            'qty_taken'           => $this->qtyTaken,
            'remaining_weight'    => max($remainingWeight, 0),
            'remaining_package'   => $perPackage > 0
                ? floor(max($remainingWeight, 0) / $perPackage)
                : 0,
            'created_at'          => now(),
            'updated_at'          => now(),
        ];
    }
}
