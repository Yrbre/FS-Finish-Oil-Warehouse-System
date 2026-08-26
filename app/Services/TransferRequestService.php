<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ReceiptOfGoods;
use App\Models\StockLedger;
use App\Models\TransferRequest;
use App\Models\User;
use App\Repositories\Interfaces\TransferRequestRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TransferRequestService implements TransferRequestServiceInterface
{
    public function __construct(
        protected TransferRequestRepositoryInterface $transferRequestRepository,
        protected ItemLocationServiceInterface $itemLocationService,
        protected StockLedgerServiceInterface $stockLedgerService,
        protected WarehouseServiceInterface $warehouseService,
    ) {}

    public function getAll()
    {
        return $this->transferRequestRepository->getAll();
    }

    public function getById(int $id)
    {
        return $this->transferRequestRepository->getById($id);
    }

    /* ================= CREATE ================= */

    public function create(array $data, int $requestedBy)
    {
        return DB::transaction(function () use ($data, $requestedBy) {
            $perPackage = (float) $data['requested_perpackage'];
            $package    = (float) $data['requested_package'];

            // Berat SELALU hasil perkalian — disimpan untuk laporan,
            // bukan untuk perhitungan alokasi.
            $data['requested_qty']  = round($perPackage * $package, 2);
            // Generate DI DALAM transaksi bersama insert-nya, supaya
            // lock baris tidak lepas sebelum baris baru tersimpan.
            $data['transfer_code']  = TransferRequest::generateTransferCode();
            $data['status']         = TransferRequest::STATUS_NEW;
            $data['requested_by']   = $requestedBy;

            return $this->transferRequestRepository->create($data);
        });
    }

    public function getAvailablePackageSizes(int $itemId, int $demanderId): Collection
    {
        return $this->itemLocationService->getAvailablePackageSizes(
            $itemId,
            $demanderId,
            $this->sourceWarehouseIds()
        );
    }

    /* ================= REKOMENDASI ================= */

    public function getRecommendation(int $id): array
    {
        $request    = $this->transferRequestRepository->getById($id);
        $sourceIds  = $this->sourceWarehouseIds($request->destination_warehouse_id);
        $perPackage = (float) $request->requested_perpackage;

        $result = $this->itemLocationService->allocateForTransfer(
            (int) $request->item_id,
            (int) $request->department_id,
            $sourceIds,
            $perPackage,
            (float) $request->requested_package
        );

        // Seluruh lot yang memenuhi syarat (bukan hanya yang terpakai
        // FEFO) — approver boleh memilih lot lain, misalnya karena
        // isu mutu pada lot tertentu.
        $lots = $this->itemLocationService->getTransferLots(
            (int) $request->item_id,
            (int) $request->department_id,
            $sourceIds,
            $perPackage
        );

        // [item_location_id => jumlah package] hasil FEFO, untuk pre-fill form.
        $suggestions = $result->lines
            ->mapWithKeys(fn($line) => [$line->lot->id => $line->packageTaken])
            ->all();

        return [
            'allocation'    => $result,
            'lots'          => $lots,
            'suggestions'   => $suggestions,
            'shortage'      => $result->shortage,
            'is_fulfilled'  => $result->isFulfilled(),
            'total_package' => $result->totalPackage(),
            'total_weight'  => round($result->totalPackage() * $perPackage, 2),
        ];
    }

    /* ================= APPROVE ================= */

    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null, ?array $manualAllocation = null)
    {
        return DB::transaction(function () use ($id, $approvedBy, $effectiveDate, $manualAllocation) {
            // Lock supaya dua approver yang menekan tombol bersamaan
            // tidak sama-sama lolos pengecekan status.
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            $this->guardApprover($approvedBy);

            if ($request->status !== TransferRequest::STATUS_NEW) {
                throw new \Exception("Request ini sudah diproses sebelumnya.");
            }

            $lines = ! empty($manualAllocation)
                ? $this->buildManualAllocation($request, $manualAllocation)
                : $this->buildAutoAllocation($id);

            $transDate = $effectiveDate ? Carbon::parse($effectiveDate) : now();

            $details = [];

            // Dikelompokkan per gudang asal supaya bb_qty dihitung
            // sekali per gudang — kondisi SEBELUM lot-lot di gudang
            // itu dipotong, bukan per lot.
            foreach ($lines->groupBy(fn($line) => (int) $line->lot->warehouse_id) as $warehouseId => $rows) {
                $warehouseId = (int) $warehouseId;

                $bbQty = $this->itemLocationService->getTotalStock(
                    (int) $request->item_id,
                    $warehouseId,
                    (int) $request->department_id
                );

                $totalTaken = 0.0;

                foreach ($rows as $line) {

                    $details[] = $line->toDetailArray($request->id);

                    $this->itemLocationService->deductLot($line->lot->id, $line->qtyTaken);
                    $totalTaken += $line->qtyTaken;
                }

                $this->stockLedgerService->record([
                    'item_id'      => $request->item_id,
                    'warehouse_id' => $warehouseId,
                    'trans_date'   => $transDate->toDateString(),
                    'in_qty'       => 0,
                    'out_qty'      => round($totalTaken, 2),
                    'bb_qty'       => $bbQty,
                    'eb_qty'       => round($bbQty - $totalTaken, 2),
                    'doc_type'     => StockLedger::DOC_TRANSFER_OUT,
                    'ref_type'     => StockLedger::REF_TRANSFER_OUT,
                    'ref_id'       => $request->id,
                ]);
            }

            $this->transferRequestRepository->createDetails($details);

            return $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_APPROVED,
                'approved_by'   => $approvedBy,
                'approved_at'   => now(),
                'approved_date' => $transDate->toDateString(),
            ]);
        });
    }

    /* ================= TANDA TERIMA ================= */

    /**
     * Buat tanda terima barang. Inilah yang mengubah approved →
     * in_transit: stok sudah dipotong saat approve, tapi barang
     * baru dianggap berangkat setelah dokumennya terbit.
     *
     * Cetak ulang tidak memanggil method ini — hanya menaikkan
     * print_count lewat controller.
     */
    public function issueReceipt(int $id, int $issuedBy, array $data)
    {
        return DB::transaction(function () use ($id, $issuedBy, $data) {
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            $this->guardReceiptIssuer($issuedBy);

            if (! $request->isShippable()) {
                throw new \Exception(
                    "Tanda terima hanya dapat dibuat untuk request yang sudah disetujui. " .
                        "Status saat ini: " . strtoupper($request->status) . "."
                );
            }

            if ($request->receiptOfGoods) {
                throw new \Exception("Tanda terima untuk request ini sudah pernah dibuat.");
            }

            $letterDate = ! empty($data['letter_date'])
                ? Carbon::parse($data['letter_date'])
                : now();

            ReceiptOfGoods::create([
                'letter_number'       => $this->generateLetterNumber(),
                'letter_date'         => $letterDate->toDateString(),
                'transfer_request_id' => $request->id,
                'responsibility_id'   => $issuedBy,
                'photo'               => $data['photo'] ?? null,
            ]);

            return $this->transferRequestRepository->update($id, [
                'status'      => TransferRequest::STATUS_IN_TRANSIT,
                'shipped_by'  => $issuedBy,
                'shipped_at'  => now(),
                'print_count' => 1,
            ]);
        });
    }

    /**
     * Terbitkan tanda terima untuk beberapa request sekaligus.
     *
     * Semua ID divalidasi DULU sebelum ada yang diproses — kalau
     * satu saja tidak memenuhi syarat, tidak ada nomor yang terbit.
     * Ini disengaja: dokumen ini menerbitkan nomor resmi dan
     * mengubah status, jadi melewati sebagian diam-diam berbahaya.
     */

    public function issueReceiptBatch(array $ids, int $issuedBy, string $letterDate): Collection
    {
        return DB::transaction(function () use ($ids, $issuedBy, $letterDate) {
            $this->guardReceiptIssuer($issuedBy);

            $date     = Carbon::parse($letterDate);
            $requests = collect();
            $errors   = [];

            // --- Tahap 1: validasi semua ---
            foreach ($ids as $id) {
                $this->transferRequestRepository->getByIdForUpdate((int) $id);
                $request = $this->transferRequestRepository->getById((int) $id);

                if ($request->receiptOfGoods) {
                    // Sudah punya tanda terima → dianggap cetak ulang,
                    // bukan error. Ikut dicetak tanpa nomor baru.
                    $requests->push($request);
                    continue;
                }

                if (! $request->isShippable()) {
                    $errors[] = "{$request->transfer_code} (" . strtoupper($request->status) . ")";
                    continue;
                }

                $requests->push($request);
            }

            if (! empty($errors)) {
                throw new \Exception(
                    "Tanda terima hanya dapat dibuat untuk request yang sudah disetujui. " .
                        "Belum memenuhi syarat: " . implode(', ', $errors) . "."
                );
            }

            if ($requests->isEmpty()) {
                throw new \Exception("Tidak ada request yang dapat dicetak.");
            }

            // --- Tahap 2: terbitkan ---
            foreach ($requests as $request) {
                if ($request->receiptOfGoods) {
                    $request->increment('print_count');
                    continue;
                }

                ReceiptOfGoods::create([
                    'letter_number'       => $this->generateLetterNumber(),
                    'letter_date'         => $date->toDateString(),
                    'transfer_request_id' => $request->id,
                    'responsibility_id'   => $issuedBy,
                ]);

                $this->transferRequestRepository->update($request->id, [
                    'status'      => TransferRequest::STATUS_IN_TRANSIT,
                    'shipped_by'  => $issuedBy,
                    'shipped_at'  => now(),
                    'print_count' => 1,
                ]);
            }

            // Muat ulang supaya relasi receiptOfGoods yang baru ikut terbaca.
            return $requests->map(
                fn($r) => $this->transferRequestRepository->getById($r->id)
            );
        });
    }

    public function markPrinted(array $ids): void
    {
        TransferRequest::whereIn('id', $ids)->increment('print_count');
    }

    /**
     * Nomor tanda terima: 0001/IMC/VIII/2026, reset tiap tahun.
     *
     * Diurutkan berdasarkan NOMOR, bukan letter_date — karena
     * letter_date boleh di-backdate, mengurutkan pakai tanggal
     * bisa menghasilkan nomor yang sudah terpakai.
     *
     * Harus dipanggil di dalam DB::transaction() supaya lock-nya
     * bertahan sampai baris baru tersimpan.
     */
    private function generateLetterNumber(): string
    {
        $now   = now();
        $tahun = $now->format('Y');
        $bulan = $this->bulanRomawi((int) $now->format('n'));

        $last = ReceiptOfGoods::withTrashed()
            ->where('letter_number', 'like', '%/' . $tahun)
            ->orderByRaw('CAST(SUBSTRING_INDEX(letter_number, "/", 1) AS UNSIGNED) DESC')
            ->lockForUpdate()
            ->first();

        $nomor = $last
            ? ((int) explode('/', $last->letter_number)[0]) + 1
            : 1;

        return str_pad($nomor, 4, '0', STR_PAD_LEFT) . "/IMC/{$bulan}/{$tahun}";
    }

    private function bulanRomawi(int $bulan): string
    {
        return [
            1 => 'I',
            2 => 'II',
            3 => 'III',
            4 => 'IV',
            5 => 'V',
            6 => 'VI',
            7 => 'VII',
            8 => 'VIII',
            9 => 'IX',
            10 => 'X',
            11 => 'XI',
            12 => 'XII',
        ][$bulan] ?? '';
    }

    /* ================= RECEIVE ================= */

    public function receive(int $id, int $receivedBy, ?string $effectiveDate = null)
    {
        return DB::transaction(function () use ($id, $receivedBy, $effectiveDate) {
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            if (! $request->isReceivable()) {
                throw new \Exception("Barang belum dikirim atau sudah diterima sebelumnya.");
            }

            $this->guardReceiver($receivedBy, $request);

            $transDate       = $effectiveDate ? Carbon::parse($effectiveDate) : now();
            $destWarehouseId = (int) $request->destination_warehouse_id;
            $demanderId      = (int) $request->department_id;

            $bbQty = $this->itemLocationService->getTotalStock(
                (int) $request->item_id,
                $destWarehouseId,
                $demanderId
            );

            $details  = $this->transferRequestRepository->getDetails($id);
            $totalQty = 0.0;

            foreach ($details as $detail) {
                // Lot baru, TIDAK digabung dengan lot lama — supaya
                // jejak tiap pengiriman tetap terlihat dan FEFO di
                // gudang tujuan tidak kabur.
                $destLot = $this->itemLocationService->addLot([
                    'item_id'         => $request->item_id,
                    'warehouse_id'    => $destWarehouseId,
                    'demander_id'     => $demanderId,
                    'vendor_lot'      => $detail->vendor_lot,
                    'receiving_lot'   => $detail->receiving_lot,
                    'exp_date'        => $detail->exp_date?->toDateString(),
                    'production_date' => $detail->production_date?->toDateString(),
                    'package'         => $detail->package,
                    'qty_perpackage'  => $detail->qty_perpackage,
                    'qty_package'     => $detail->package_taken,
                    'received_date'   => $transDate->toDateString(),
                    'is_warehouse_stock' => true,
                ]);

                $detail->update(['dest_item_location_id' => $destLot->id]);

                $totalQty += (float) $detail->qty_taken;
            }

            $this->stockLedgerService->record([
                'item_id'      => $request->item_id,
                'warehouse_id' => $destWarehouseId,
                'trans_date'   => $transDate->toDateString(),
                'in_qty'       => round($totalQty, 2),
                'out_qty'      => 0,
                'bb_qty'       => $bbQty,
                'eb_qty'       => round($bbQty + $totalQty, 2),
                'doc_type'     => StockLedger::DOC_TRANSFER_IN,
                'ref_type'     => StockLedger::REF_TRANSFER_IN,
                'ref_id'       => $request->id,
            ]);

            return $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_RECEIVED,
                'received_by'   => $receivedBy,
                'received_at'   => now(),
                'received_date' => $transDate->toDateString(),
            ]);
        });
    }

    /* ================= REJECT / CANCEL ================= */

    public function reject(int $id, int $rejectedBy, string $reason)
    {
        return DB::transaction(function () use ($id, $rejectedBy, $reason) {
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            $this->guardApprover($rejectedBy);

            if ($request->status !== TransferRequest::STATUS_NEW) {
                throw new \Exception("Request ini sudah diproses, tidak dapat ditolak.");
            }

            return $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_REJECTED,
                'rejected_by'   => $rejectedBy,
                'rejected_at'   => now(),
                'reject_reason' => $reason,
            ]);
        });
    }

    public function cancel(int $id, int $cancelledBy)
    {
        return DB::transaction(function () use ($id, $cancelledBy) {
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            if ((int) $request->requested_by !== $cancelledBy) {
                throw new \Exception("Hanya pembuat request yang dapat membatalkan.");
            }

            if (! $request->isCancellable()) {
                throw new \Exception("Request tidak dapat dibatalkan karena sudah diproses.");
            }

            return $this->transferRequestRepository->update($id, [
                'status'       => TransferRequest::STATUS_CANCELLED,
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
            ]);
        });
    }

    /* ================= PRIVATE ================= */

    /**
     * Gudang IMC sebagai sumber transfer.
     *
     * Gudang tujuan dikecualikan untuk kasus IMC memindahkan barang
     * antar gudangnya sendiri — supaya tidak "mengirim" dari dan ke
     * gudang yang sama.
     */
    private function sourceWarehouseIds($destinationWarehouseId = null): array
    {
        $ids = $this->warehouseService->getIdsByDepartmentCode(Department::CODE_IMC);

        if ($destinationWarehouseId) {
            $ids = array_diff($ids, [(int) $destinationWarehouseId]);
        }

        return array_values($ids);
    }

    private function buildAutoAllocation(int $id)
    {
        $rec = $this->getRecommendation($id);

        if (! $rec['is_fulfilled']) {
            throw new \Exception(
                "Stok tidak mencukupi. Kurang " .
                    number_format($rec['shortage'], 2, ',', '.') . " package."
            );
        }

        return $rec['allocation']->lines;
    }

    /**
     * Alokasi manual divalidasi ulang dari NOL terhadap stok terkini —
     * data dari form tidak dipercaya begitu saja.
     *
     * $manualAllocation: [item_location_id => jumlah_package, ...]
     */
    private function buildManualAllocation(TransferRequest $request, array $manualAllocation)
    {
        $sourceIds  = $this->sourceWarehouseIds($request->destination_warehouse_id);
        $perPackage = (float) $request->requested_perpackage;

        $lines        = collect();
        $totalPackage = 0.0;

        foreach ($manualAllocation as $lotId => $package) {
            $package = (float) $package;

            if ($package <= 0) {
                continue;
            }

            if (floor($package) != $package) {
                throw new \Exception("Jumlah package harus bilangan bulat — kemasan di IMC tidak boleh terbuka.");
            }

            $lot = $this->itemLocationService->getById((int) $lotId);

            if ((int) $lot->item_id !== (int) $request->item_id) {
                throw new \Exception("Lot #{$lotId} bukan untuk item yang sama dengan request ini.");
            }

            if ((int) $lot->demander_id !== (int) $request->department_id) {
                throw new \Exception("Lot #{$lotId} bukan milik department pemohon.");
            }

            if (! in_array((int) $lot->warehouse_id, $sourceIds, true)) {
                throw new \Exception("Lot #{$lotId} bukan berasal dari gudang IMC yang sah.");
            }

            if ((float) $lot->qty_perpackage !== $perPackage) {
                throw new \Exception(
                    "Lot #{$lotId} ukurannya " . number_format((float) $lot->qty_perpackage, 2, ',', '.') .
                        " kg, tidak sesuai permintaan " . number_format($perPackage, 2, ',', '.') . " kg."
                );
            }

            $availablePackage = floor((float) $lot->qty_weight / $perPackage);

            if ($package > $availablePackage) {
                throw new \Exception(
                    "Lot " . ($lot->receiving_lot ?? "#{$lot->id}") . " hanya tersedia " .
                        $availablePackage . " package utuh."
                );
            }

            $lines->push(new \App\Services\Dto\AllocationLine(
                lot: $lot,
                packageTaken: $package,
                qtyTaken: round($package * $perPackage, 2),
            ));

            $totalPackage += $package;
        }

        if ($lines->isEmpty()) {
            throw new \Exception("Belum ada lot yang dipilih untuk dikirim.");
        }

        if ($totalPackage != (float) $request->requested_package) {
            throw new \Exception(
                "Total package yang dipilih ({$totalPackage}) harus sama dengan yang diminta (" .
                    (float) $request->requested_package . ")."
            );
        }

        return $lines;
    }

    private function guardApprover(int $userId): void
    {
        if (! $this->transferRequestRepository->isApprover($userId)) {
            throw new \Exception("Anda tidak memiliki wewenang untuk memproses Permintaan Kirim Barang.");
        }
    }

    /**
     * Akses cetak diatur per user, bukan per role — di lapangan
     * yang mengurus dokumen belum tentu orang yang sama dengan
     * approver.
     */
    private function guardReceiptIssuer(int $userId): void
    {
        if (! User::findOrFail($userId)->canIssueReceipt()) {
            throw new \Exception("Anda tidak memiliki akses untuk membuat tanda terima barang.");
        }
    }

    /**
     * Yang mengkonfirmasi terima harus dari department tujuan.
     * Tanpa ini, siapa pun berpermission receive bisa mengkonfirmasi
     * barang milik department lain.
     */
    private function guardReceiver(int $userId, TransferRequest $request): void
    {
        $user = User::findOrFail($userId);

        if ($user->hasRole('admin')) {
            return;
        }

        if ((int) $user->department_id !== (int) $request->department_id) {
            throw new \Exception("Hanya department tujuan yang dapat mengkonfirmasi penerimaan barang.");
        }
    }
}
