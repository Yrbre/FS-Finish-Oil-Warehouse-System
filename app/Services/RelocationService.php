<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ItemRelocation;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Illuminate\Support\Facades\DB;

/**
 * Pemindahan lot antar gudang/rak IMC.
 *
 * Berbeda dari transfer: barang tidak berpindah kepemilikan dan
 * tidak keluar dari IMC — hanya berpindah tempat penyimpanan.
 * Karena itu TIDAK dicatat di stock_ledger (saldo total tidak
 * berubah), melainkan di tabel audit item_relocations.
 */
class RelocationService
{
    public function __construct(
        protected ItemLocationRepositoryInterface $itemLocationRepository,
        protected ItemLocationServiceInterface $itemLocationService,
        protected WarehouseServiceInterface $warehouseService,
    ) {}

    public function relocate(
        int $itemLocationId,
        int $toWarehouseId,
        float $packageMoved,
        int $movedBy,
        ?string $reason = null
    ): ItemRelocation {
        return DB::transaction(function () use ($itemLocationId, $toWarehouseId, $packageMoved, $movedBy, $reason) {
            $lot = $this->itemLocationRepository->getByIdForUpdate($itemLocationId);

            $this->guard($lot, $toWarehouseId, $packageMoved);

            $perPackage = (float) $lot->qty_perpackage;
            $qtyMoved   = round($packageMoved * $perPackage, 2);

            $fromWarehouse = $lot->warehouse;
            $toWarehouse   = $this->warehouseService->getById($toWarehouseId);

            // Potong lot asal
            $this->itemLocationService->deductLot($lot->id, $qtyMoved);

            // Lot baru di gudang tujuan — pemilik dan seluruh identitas
            // lot disalin apa adanya. Yang berubah hanya lokasinya.
            $newLot = $this->itemLocationService->addLot([
                'item_id'            => $lot->item_id,
                'warehouse_id'       => $toWarehouseId,
                'demander_id'        => $lot->demander_id,
                'vendor_lot'         => $lot->vendor_lot,
                'receiving_lot'      => $lot->receiving_lot,
                'production_date'    => $lot->production_date?->toDateString(),
                'exp_date'           => $lot->exp_date?->toDateString(),
                'qty_perpackage'     => $perPackage,
                'qty_package'        => $packageMoved,
                'package'            => $lot->package,
                'received_date'      => $lot->received_date?->toDateString(),
                'is_warehouse_stock' => true,
            ]);

            return ItemRelocation::create([
                'item_id'               => $lot->item_id,
                'from_item_location_id' => $lot->id,
                'to_item_location_id'   => $newLot->id,
                'from_warehouse_id'     => $fromWarehouse->id,
                'to_warehouse_id'       => $toWarehouseId,
                // Tag disnapshot karena nama/lokasi gudang bisa diubah
                // kemudian — riwayat harus menampilkan kondisi saat itu.
                'from_tag'              => $fromWarehouse->tag,
                'to_tag'                => $toWarehouse->tag,
                'demander_id'           => $lot->demander_id,
                'qty_perpackage'        => $perPackage,
                'package_moved'         => $packageMoved,
                'qty_moved'             => $qtyMoved,
                'reason'                => $reason,
                'moved_by'              => $movedBy,
                'moved_at'              => now(),
            ]);
        });
    }

    private function guard($lot, int $toWarehouseId, float $packageMoved): void
    {
        if ($lot->disposed_at !== null) {
            throw new \Exception("Lot ini sudah dibuang, tidak dapat dipindahkan.");
        }

        if ((int) $lot->warehouse_id === $toWarehouseId) {
            throw new \Exception("Gudang tujuan sama dengan gudang asal.");
        }

        // Pemindahan hanya berlaku di lingkungan IMC. Memindahkan
        // barang ke gudang department berarti menyerahkannya —
        // itu urusan Transfer Request, bukan relokasi.
        if (! $lot->warehouse->isImc()) {
            throw new \Exception("Hanya lot di gudang IMC yang dapat dipindahkan.");
        }

        $toWarehouse = $this->warehouseService->getById($toWarehouseId);

        if (! $toWarehouse->isImc()) {
            throw new \Exception(
                "Gudang tujuan harus milik IMC. Untuk mengirim barang ke department, " .
                    "gunakan Permintaan Kirim Barang."
            );
        }

        if ($packageMoved <= 0) {
            throw new \Exception("Jumlah package harus lebih dari 0.");
        }

        if (floor($packageMoved) != $packageMoved) {
            throw new \Exception("Jumlah package harus bilangan bulat — kemasan di IMC tidak boleh terbuka.");
        }

        $perPackage = (float) $lot->qty_perpackage;
        $available  = $perPackage > 0 ? floor((float) $lot->qty_weight / $perPackage) : 0;

        if ($packageMoved > $available) {
            throw new \Exception("Lot ini hanya tersedia {$available} package utuh.");
        }
    }
}
