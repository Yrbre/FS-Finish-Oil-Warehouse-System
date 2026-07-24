<?php

namespace App\Services\Interfaces;

interface TransferRequestServiceInterface
{
    public function getAll();
    public function getById(int $id);

    /**
     * Buat request baru. User hanya mengisi item, qty, gudang tujuan,
     * tanggal barang seharusnya sampai, dan catatan.
     * Gudang asal TIDAK diisi user — ditentukan sistem saat approval.
     */
    public function create(array $data, int $requestedBy);

    /**
     * Rekomendasi FEFO lintas gudang untuk memenuhi request.
     * Dihitung live (tanpa reservasi stok), dipakai IMC sebagai bahan
     * pertimbangan sebelum approve.
     *
     * Return: [
     *   'allocation'    => array of ['item_location' => ItemLocation, 'qty_to_take' => float],
     *   'remaining_qty' => float,   // > 0 berarti stok seluruh gudang tidak cukup
     *   'is_fulfilled'  => bool,
     * ]
     */
    public function getRecommendation(int $id): array;

    /**
     * Approve oleh IMC — stok keluar dari gudang-gudang asal (FEFO),
     * breakdown lot dicatat, status jadi in_transit.
     * $effectiveDate bisa diisi untuk backdate.
     */
    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null);

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
