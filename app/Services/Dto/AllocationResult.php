<?php

namespace App\Services\Dto;

use Illuminate\Support\Collection;

class AllocationResult
{
    /** @param Collection<int, AllocationLine> $lines */
    public function __construct(
        public readonly Collection $lines,
        public readonly float $requested,
        public readonly float $allocated,
        public readonly float $shortage,
    ) {}

    public function isFulfilled(): bool
    {
        return $this->shortage <= 0.001;
    }

    public function isEmpty(): bool
    {
        return $this->lines->isEmpty();
    }

    public function totalPackage(): float
    {
        return round((float) $this->lines->sum('packageTaken'), 2);
    }

    /**
     * Alokasi dikelompokkan per gudang asal.
     * Dipakai saat mencatat ledger — bb_qty dihitung sekali per
     * gudang, bukan per lot.
     *
     * @return Collection<int, Collection<int, AllocationLine>>
     */
    public function groupedByWarehouse(): Collection
    {
        return $this->lines->groupBy(fn(AllocationLine $line) => (int) $line->lot->warehouse_id);
    }

    public function toDetailArray(int $transferRequestId): array
    {
        return $this->lines
            ->map(fn(AllocationLine $line) => $line->toDetailArray($transferRequestId))
            ->all();
    }
}
