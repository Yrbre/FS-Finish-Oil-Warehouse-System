<?php

namespace App\Services\Interfaces;

use Illuminate\Support\Collection;

interface TransferRequestServiceInterface
{
    public function getAll();
    public function getById(int $id);

    /**
     * Buat request baru. User mengisi item, ukuran kemasan, jumlah
     * package, gudang tujuan, tanggal kebutuhan, dan catatan.
     *
     * requested_qty (kg) dihitung sistem dari package x ukuran.
     * Gudang asal TIDAK diisi user — ditentukan sistem lewat FEFO
     * saat approval.
     */
    public function create(array $data, int $requestedBy);

    /** Ukuran kemasan yang tersedia untuk department ini di gudang IMC. */
    public function getAvailablePackageSizes(int $itemId, int $demanderId): Collection;

    /**
     * Rekomendasi FEFO untuk memenuhi request. Dihitung live tanpa
     * memotong stok — dipakai IMC sebagai bahan pertimbangan
     * sebelum approve.
     *
     * Hanya mencari lot di gudang IMC yang MILIK department pemohon
     * dan ukurannya sama persis dengan yang diminta.
     *
     * Return: [
     *   'allocation'    => AllocationResult,
     *   'shortage'      => float,  // > 0 berarti package tidak cukup
     *   'is_fulfilled'  => bool,
     *   'total_package' => float,
     *   'total_weight'  => float,
     * ]
     */
    public function getRecommendation(int $id): array;

    /**
     * Approve oleh IMC — stok dipotong dari gudang asal (FEFO),
     * breakdown lot dicatat, status jadi APPROVED.
     *
     * Barang belum dianggap berangkat di titik ini; status baru
     * menjadi in_transit setelah tanda terima diterbitkan lewat
     * issueReceipt() atau issueReceiptBatch().
     *
     * $manualAllocation: [item_location_id => jumlah package].
     * $effectiveDate bisa diisi untuk backdate.
     */
    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null, ?array $manualAllocation = null);

    /** Buat tanda terima barang, sekaligus approved → in_transit. */
    public function issueReceipt(int $id, int $issuedBy, array $data);

    /** Buat tanda terima untuk beberapa request sekaligus. */
    public function issueReceiptBatch(array $ids, int $issuedBy, string $letterDate): Collection;

    /** Naikkan print_count untuk cetak ulang. */
    public function markPrinted(array $ids): void;

    /**
     * Konfirmasi barang sampai di gudang tujuan — stok masuk sesuai
     * breakdown lot, status jadi received.
     */
    public function receive(int $id, int $receivedBy, ?string $effectiveDate = null);

    /**
     * Tolak request. Hanya bisa selama status masih new.
     */
    public function reject(int $id, int $rejectedBy, string $reason);

    /**
     * Batalkan request oleh requester sendiri. Hanya bisa selama
     * status masih new (belum ada approval maupun penolakan).
     */
    public function cancel(int $id, int $cancelledBy);
}
