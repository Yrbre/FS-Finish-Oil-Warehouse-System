<?php

namespace App\Services\Interfaces;

use Illuminate\Support\Collection;

interface TransferRequestServiceInterface
{
    public function getAll();
    public function getById(int $id);

    /** $data['items'] berisi beberapa baris item. */
    public function create(array $data, int $requestedBy);

    public function getAvailablePackageSizes(int $itemId, int $demanderId): Collection;

    /** Rekomendasi FEFO untuk SEMUA item aktif dalam request. */
    public function getRecommendation(int $id): array;

    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null, ?array $manualAllocation = null);

    public function issueReceipt(int $id, int $issuedBy, array $data);
    public function issueReceiptBatch(array $ids, int $issuedBy, string $letterDate): Collection;
    public function markPrinted(array $ids): void;
    public function receive(int $id, int $receivedBy, ?string $effectiveDate = null);

    /* ---- Aksi per item ---- */

    /** Tolak satu item (hanya saat status new). */
    public function rejectItem(int $itemId, int $rejectedBy, string $reason);

    /** Batalkan satu item oleh pemohon (hanya saat status new). */
    public function cancelItem(int $itemId, int $cancelledBy);

    /**
     * Batalkan item yang SUDAH approved — stok dikembalikan ke lot
     * asal. Hanya IMC, dan hanya sebelum TTB terbit.
     */
    public function cancelApprovedItem(int $itemId, int $cancelledBy, string $reason);
}
