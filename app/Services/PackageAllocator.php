<?php

namespace App\Services;

use App\Models\ItemLocation;
use App\Services\Dto\AllocationLine;
use App\Services\Dto\AllocationResult;
use Illuminate\Support\Collection;

/**
 * Alokasi stok berbasis FEFO dengan dua mode:
 *
 *  1. allocateByPackage() — untuk TRANSFER.
 *     Memindahkan package UTUH, jadi ukuran kemasan harus cocok persis.
 *     Lot 200kg/pkg tidak bisa melayani permintaan 100kg/pkg meski
 *     total beratnya cukup.
 *
 *  2. allocateByWeight() — untuk CONS.
 *     Memotong kg dari lot manapun, tidak peduli ukuran kemasan.
 *     Di gudang staff barang sudah dipakai per kg untuk produksi.
 *
 * Keduanya WAJIB memfilter demander_id — stok satu department
 * tidak boleh diambil department lain.
 */
class PackageAllocator
{
    /**
     * Toleransi pembanding float. Dipakai supaya sisa 0.0000001 kg
     * akibat pembulatan tidak dianggap "masih kurang".
     */
    private const EPSILON = 0.001;

    /**
     * Alokasi untuk TRANSFER — package utuh, ukuran harus cocok.
     *
     * @param Collection<int, ItemLocation> $lots sudah terurut FEFO
     */
    public function allocateByPackage(Collection $lots, float $packageNeeded): AllocationResult
    {
        $lines     = collect();
        $remaining = $packageNeeded;

        foreach ($lots as $lot) {
            if ($remaining <= self::EPSILON) {
                break;
            }

            $availablePackage = $this->availablePackage($lot);

            if ($availablePackage <= 0) {
                continue;
            }

            // Hanya package utuh yang boleh diambil dari IMC.
            $take = min(floor($availablePackage), floor($remaining));

            if ($take <= 0) {
                continue;
            }

            $lines->push(new AllocationLine(
                lot: $lot,
                packageTaken: $take,
                // Berat SELALU hasil perkalian, tidak pernah dibagi.
                qtyTaken: round($take * (float) $lot->qty_perpackage, 2),
            ));

            $remaining -= $take;
        }

        return new AllocationResult(
            lines: $lines,
            requested: $packageNeeded,
            allocated: $packageNeeded - $remaining,
            shortage: max($remaining, 0),
        );
    }

    /**
     * Alokasi untuk CONS — potong kg, lintas ukuran kemasan.
     *
     * @param Collection<int, ItemLocation> $lots sudah terurut FEFO
     */
    public function allocateByWeight(Collection $lots, float $weightNeeded): AllocationResult
    {
        $lines     = collect();
        $remaining = $weightNeeded;

        foreach ($lots as $lot) {
            if ($remaining <= self::EPSILON) {
                break;
            }

            $availableWeight = (float) $lot->qty_weight;

            if ($availableWeight <= 0) {
                continue;
            }

            $take = min($availableWeight, $remaining);
            $take = round($take, 2);

            if ($take <= 0) {
                continue;
            }

            $lines->push(new AllocationLine(
                lot: $lot,
                // Package terpakai dicatat sebagai informasi saja —
                // qty_package di lot tidak pernah di-update saat CONS.
                packageTaken: $this->weightToPackage($take, (float) $lot->qty_perpackage),
                qtyTaken: $take,
            ));

            $remaining -= $take;
        }

        return new AllocationResult(
            lines: $lines,
            requested: $weightNeeded,
            allocated: round($weightNeeded - $remaining, 2),
            shortage: max(round($remaining, 2), 0),
        );
    }

    /**
     * Sisa package utuh di sebuah lot, dihitung dari berat.
     *
     * Sengaja tidak membaca kolom qty_package karena kolom itu
     * tidak di-update saat CONS — berat adalah satu-satunya
     * sumber kebenaran.
     */
    private function availablePackage(ItemLocation $lot): float
    {
        $perPackage = (float) $lot->qty_perpackage;

        if ($perPackage <= 0) {
            return 0.0;
        }

        return (float) $lot->qty_weight / $perPackage;
    }

    private function weightToPackage(float $weight, float $perPackage): float
    {
        return $perPackage > 0 ? round($weight / $perPackage, 2) : 0.0;
    }
}
