<?php

namespace App\Services;

use App\Models\Department;
use App\Models\ReceiptOfGoods;
use App\Models\StockLedger;
use App\Models\TransferRequest;
use App\Models\TransferRequestItem;
use App\Models\User;
use App\Notifications\TransferRequestNotification;
use App\Repositories\Interfaces\ItemLocationRepositoryInterface;
use App\Repositories\Interfaces\StockLedgerRepositoryInterface;
use App\Repositories\Interfaces\TransferRequestRepositoryInterface;
use App\Services\Interfaces\ItemLocationServiceInterface;
use App\Services\Interfaces\StockLedgerServiceInterface;
use App\Services\Interfaces\TransferRequestServiceInterface;
use App\Services\Interfaces\WarehouseServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransferRequestService implements TransferRequestServiceInterface
{
    public function __construct(
        protected TransferRequestRepositoryInterface $transferRequestRepository,
        protected ItemLocationServiceInterface $itemLocationService,
        protected StockLedgerServiceInterface $stockLedgerService,
        protected StockLedgerRepositoryInterface $stockLedgerRepository,
        protected WarehouseServiceInterface $warehouseService,
        protected ItemLocationRepositoryInterface $itemLocationRepository,
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
            $items = $data['items'] ?? [];
            unset($data['items']);

            if (empty($items)) {
                throw new \Exception("Minimal harus ada 1 item dalam permintaan.");
            }

            $data['transfer_code'] = TransferRequest::generateTransferCode();
            $data['status']        = TransferRequest::STATUS_NEW;
            $data['requested_by']  = $requestedBy;

            $request = $this->transferRequestRepository->create($data);

            foreach ($items as $i => $row) {
                $perPackage = (float) $row['requested_perpackage'];
                $package    = (float) $row['requested_package'];

                $this->guardRequestable(
                    (int) $row['item_id'],
                    (int) $data['department_id'],
                    $perPackage,
                    $package,
                    $i + 1
                );

                TransferRequestItem::create([
                    'transfer_request_id'  => $request->id,
                    'item_id'              => $row['item_id'],
                    'requested_perpackage' => $perPackage,
                    'requested_package'    => $package,
                    // Berat SELALU hasil perkalian, untuk laporan saja.
                    'requested_qty'        => round($perPackage * $package, 2),
                    'status'               => TransferRequestItem::STATUS_NEW,
                ]);
            }

            $request->load(['items', 'department', 'destinationWarehouse']);

            $this->notifyUsers(
                $this->approverUsers(),
                new TransferRequestNotification($request, TransferRequestNotification::CREATED)
            );

            return $request;
        });
    }

    /**
     * Cegah permintaan melebihi stok yang benar-benar bisa dipakai.
     *
     * Stok tersedia = package utuh milik department ini di gudang IMC,
     * DIKURANGI yang sudah dipesan request lain yang masih menunggu.
     */
    private function guardRequestable(
        int $itemId,
        int $demanderId,
        float $perPackage,
        float $package,
        int $rowNo
    ): void {
        $sizes = $this->itemLocationService->getAvailablePackageSizes(
            $itemId,
            $demanderId,
            $this->sourceWarehouseIds()
        );

        $size = $sizes->firstWhere('qty_perpackage', $perPackage);

        if (! $size) {
            throw new \Exception(
                "Item baris ke-{$rowNo}: ukuran " . number_format($perPackage, 2, ',', '.') .
                    " kg tidak tersedia di gudang IMC untuk department Anda."
            );
        }

        if ($package > $size->available_package) {
            $pesan = "Item baris ke-{$rowNo}: hanya tersedia {$size->available_package} package";

            if ($size->reserved_package > 0) {
                $pesan .= " (dari {$size->physical_package} package, " .
                    "{$size->reserved_package} sudah dipesan permintaan lain yang belum diproses)";
            }

            throw new \Exception($pesan . ".");
        }
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

    /**
     * Rekomendasi FEFO per item. Item yang sudah ditolak atau
     * dibatalkan dilewati.
     *
     * Return: [
     *   'items'        => [ ['item' => TransferRequestItem, 'allocation' => AllocationResult, ...], ... ],
     *   'all_fulfilled'=> bool,
     * ]
     */
    public function getRecommendation(int $id): array
    {
        $request   = $this->transferRequestRepository->getById($id);
        $sourceIds = $this->sourceWarehouseIds($request->destination_warehouse_id);

        $rows         = [];
        $allFulfilled = true;

        foreach ($request->items as $trItem) {
            if ($trItem->isVoid()) {
                continue;
            }

            $perPackage = (float) $trItem->requested_perpackage;

            $result = $this->itemLocationService->allocateForTransfer(
                (int) $trItem->item_id,
                (int) $request->department_id,
                $sourceIds,
                $perPackage,
                (float) $trItem->requested_package
            );

            $lots = $this->itemLocationService->getTransferLots(
                (int) $trItem->item_id,
                (int) $request->department_id,
                $sourceIds,
                $perPackage
            );

            $suggestions = $result->lines
                ->mapWithKeys(fn($line) => [$line->lot->id => $line->packageTaken])
                ->all();

            if (! $result->isFulfilled()) {
                $allFulfilled = false;
            }

            $rows[] = [
                'item'          => $trItem,
                'allocation'    => $result,
                'lots'          => $lots,
                'suggestions'   => $suggestions,
                'shortage'      => $result->shortage,
                'is_fulfilled'  => $result->isFulfilled(),
                'total_package' => $result->totalPackage(),
                'total_weight'  => round($result->totalPackage() * $perPackage, 2),
            ];
        }

        return [
            'items'         => $rows,
            'all_fulfilled' => $allFulfilled,
        ];
    }

        /* ================= APPROVE ================= */

    /**
     * Approve seluruh item aktif dalam request.
     *
     * $manualAllocation: [transfer_request_item_id => [item_location_id => package]]
     * Item yang tidak ada di array ini memakai saran FEFO.
     */
    public function approve(int $id, int $approvedBy, ?string $effectiveDate = null, ?array $manualAllocation = null)
    {
        return DB::transaction(function () use ($id, $approvedBy, $effectiveDate, $manualAllocation) {
            $this->transferRequestRepository->getByIdForUpdate($id);
            $request = $this->transferRequestRepository->getById($id);

            $this->guardApprover($approvedBy);

            if ($request->status !== TransferRequest::STATUS_NEW) {
                throw new \Exception("Request ini sudah diproses sebelumnya.");
            }

            $pendingItems = $request->items->filter(fn($i) => $i->isPending());

            if ($pendingItems->isEmpty()) {
                throw new \Exception("Tidak ada item yang dapat disetujui.");
            }

            $transDate = $effectiveDate ? Carbon::parse($effectiveDate) : now();

            foreach ($pendingItems as $trItem) {
                $manual = $manualAllocation[$trItem->id] ?? null;

                $lines = ! empty($manual)
                    ? $this->buildManualAllocation($request, $trItem, $manual)
                    : $this->buildAutoAllocation($request, $trItem);

                $this->commitAllocation($request, $trItem, $lines, $transDate);

                $trItem->update(['status' => TransferRequestItem::STATUS_APPROVED]);
            }

            $this->transferRequestRepository->update($id, [
                'approved_by'   => $approvedBy,
                'approved_at'   => now(),
                'approved_date' => $transDate->toDateString(),
            ]);


            $request->refresh()->syncStatusFromItems();

            $fresh = $this->transferRequestRepository->getById($id);

            $this->notifyUsers(
                $this->requesterUser($fresh),
                new TransferRequestNotification($fresh, TransferRequestNotification::APPROVED)
            );

            return $fresh;
        });
    }


    /**
     * Potong stok, simpan detail, catat ledger — untuk SATU item.
     *
     * Ledger dikelompokkan per gudang asal supaya bb_qty dihitung
     * sekali per gudang, bukan per lot. ref_id menunjuk ke
     * transfer_request_items.id agar pembatalan per item bisa
     * menghapus ledger miliknya saja.
     */
    private function commitAllocation(
        TransferRequest $request,
        TransferRequestItem $trItem,
        $lines,
        Carbon $transDate
    ): void {
        $details = [];

        foreach ($lines->groupBy(fn($line) => (int) $line->lot->warehouse_id) as $warehouseId => $rows) {
            $warehouseId = (int) $warehouseId;

            $bbQty = $this->itemLocationService->getTotalStock(
                (int) $trItem->item_id,
                $warehouseId,
                (int) $request->department_id
            );

            $totalTaken = 0.0;

            foreach ($rows as $line) {
                // Snapshot DULU — toDetailArray() membaca qty_weight
                // lot sebelum dipotong.
                $details[] = $line->toDetailArray($trItem->id);

                $this->itemLocationService->deductLot($line->lot->id, $line->qtyTaken);
                $totalTaken += $line->qtyTaken;
            }

            $this->stockLedgerService->record([
                'item_id'      => $trItem->item_id,
                'warehouse_id' => $warehouseId,
                'trans_date'   => $transDate->toDateString(),
                'in_qty'       => 0,
                'out_qty'      => round($totalTaken, 2),
                'bb_qty'       => $bbQty,
                'eb_qty'       => round($bbQty - $totalTaken, 2),
                'doc_type'     => StockLedger::DOC_TRANSFER_OUT,
                'ref_type'     => StockLedger::REF_TRANSFER_OUT,
                'ref_id'       => $trItem->id,
            ]);
        }

        $this->transferRequestRepository->createDetails($details);
    }

        /* ================= AKSI PER ITEM ================= */

    /**
     * Tolak satu item oleh IMC. Hanya saat item masih new —
     * stok belum dipotong, jadi tidak ada yang perlu dikembalikan.
     */
    public function rejectItem(int $itemId, int $rejectedBy, string $reason)
    {
        return DB::transaction(function () use ($itemId, $rejectedBy, $reason) {
            $trItem = TransferRequestItem::lockForUpdate()->findOrFail($itemId);

            $this->guardApprover($rejectedBy);

            if (! $trItem->isPending()) {
                throw new \Exception(
                    "Item ini sudah diproses (status: " . strtoupper($trItem->status) . "), tidak dapat ditolak."
                );
            }

            $trItem->update([
                'status'        => TransferRequestItem::STATUS_REJECTED,
                'rejected_by'   => $rejectedBy,
                'rejected_at'   => now(),
                'reject_reason' => $reason,
            ]);


            $request = $trItem->transferRequest;
            $request->syncStatusFromItems();

            $request->load(['department', 'destinationWarehouse', 'items']);

            $this->notifyUsers(
                $this->requesterUser($request),
                new TransferRequestNotification(
                    $request,
                    TransferRequestNotification::REJECTED,
                    $trItem->item->item_desc . ' — ' . $reason
                )
            );

            return $trItem;
        });
    }

    /**
     * Batalkan satu item oleh pemohon sendiri, selama masih new.
     */
    public function cancelItem(int $itemId, int $cancelledBy)
    {
        return DB::transaction(function () use ($itemId, $cancelledBy) {
            $trItem  = TransferRequestItem::lockForUpdate()->findOrFail($itemId);
            $request = $trItem->transferRequest;

            if ((int) $request->requested_by !== $cancelledBy) {
                throw new \Exception("Hanya pembuat request yang dapat membatalkan.");
            }

            if (! $trItem->isPending()) {
                throw new \Exception("Item ini sudah diproses, tidak dapat dibatalkan.");
            }

            $trItem->update([
                'status'       => TransferRequestItem::STATUS_CANCELLED,
                'cancelled_by' => $cancelledBy,
                'cancelled_at' => now(),
            ]);

            $request->syncStatusFromItems();

            return $trItem;
        });
    }

    /**
     * Batalkan item yang SUDAH approved — stok dikembalikan ke lot asal.
     *
     * Hanya IMC, dan hanya sebelum tanda terima terbit. Setelah TTB
     * ada, barang sudah dianggap berangkat dan pembatalan bukan lagi
     * urusan sistem stok.
     */
    public function cancelApprovedItem(int $itemId, int $cancelledBy, string $reason)
    {
        return DB::transaction(function () use ($itemId, $cancelledBy, $reason) {
            $trItem = TransferRequestItem::with('details')->lockForUpdate()->findOrFail($itemId);
            $this->transferRequestRepository->getByIdForUpdate($trItem->transfer_request_id);
            $request = $this->transferRequestRepository->getById($trItem->transfer_request_id);

            $this->guardApprover($cancelledBy);

            if (! $trItem->isApproved()) {
                throw new \Exception(
                    "Hanya item yang sudah disetujui yang dapat dibatalkan di sini. " .
                        "Status saat ini: " . strtoupper($trItem->status) . "."
                );
            }

            if ($request->receiptOfGoods) {
                throw new \Exception(
                    "Tanda terima sudah diterbitkan, item tidak dapat dibatalkan lagi."
                );
            }

            // Kembalikan stok ke lot asal.
            foreach ($trItem->details as $detail) {
                // Dikunci karena lot ini akan ditambah — tanpa kunci,
                // perubahan bersamaan dari CONS bisa saling menimpa.
                $lot = $this->itemLocationRepository->getByIdForUpdate((int) $detail->item_location_id);

                if ($lot->disposed_at !== null) {
                    throw new \Exception(
                        "Lot " . ($lot->receiving_lot ?? "#{$lot->id}") .
                            " sudah dibuang, stok tidak dapat dikembalikan."
                    );
                }

                $this->itemLocationService->update($lot->id, [
                    'qty_weight' => round((float) $lot->qty_weight + (float) $detail->qty_taken, 2),
                ]);
            }

            // Hapus jejak alokasi & ledger — pembatalan sebelum barang
            // berangkat dianggap tidak pernah terjadi, bukan mutasi baru.
            $trItem->details()->delete();
            $this->stockLedgerRepository->deleteByRef(StockLedger::REF_TRANSFER_OUT, $trItem->id);

            $trItem->update([
                'status'        => TransferRequestItem::STATUS_CANCELLED,
                'cancelled_by'  => $cancelledBy,
                'cancelled_at'  => now(),
                'cancel_reason' => $reason,
            ]);

            $request->refresh()->syncStatusFromItems();

            return $trItem;
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

            $updated = $this->transferRequestRepository->update($id, [
                'status'      => TransferRequest::STATUS_IN_TRANSIT,
                'shipped_by'  => $issuedBy,
                'shipped_at'  => now(),
                'print_count' => 1,
            ]);

            // Semua user di department tujuan perlu tahu barang
            // sudah berangkat supaya siap mengkonfirmasi.
            $this->notifyUsers(
                $this->departmentUsers((int) $request->department_id),
                new TransferRequestNotification($request, TransferRequestNotification::SHIPPED)
            );

            return $updated;
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

                $this->notifyUsers(
                    $this->departmentUsers((int) $request->department_id),
                    new TransferRequestNotification($request, TransferRequestNotification::SHIPPED)
                );
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

            foreach ($request->activeItems()->with('details')->get() as $trItem) {
                $bbQty = $this->itemLocationService->getTotalStock(
                    (int) $trItem->item_id,
                    $destWarehouseId,
                    $demanderId
                );

                $itemQty = 0.0;

                foreach ($trItem->details as $detail) {
                    $destLot = $this->itemLocationService->addLot([
                        'item_id'         => $trItem->item_id,
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
                    $itemQty += (float) $detail->qty_taken;
                }

                $this->stockLedgerService->record([
                    'item_id'      => $trItem->item_id,
                    'warehouse_id' => $destWarehouseId,
                    'trans_date'   => $transDate->toDateString(),
                    'in_qty'       => round($itemQty, 2),
                    'out_qty'      => 0,
                    'bb_qty'       => $bbQty,
                    'eb_qty'       => round($bbQty + $itemQty, 2),
                    'doc_type'     => StockLedger::DOC_TRANSFER_IN,
                    'ref_type'     => StockLedger::REF_TRANSFER_IN,
                    'ref_id'       => $trItem->id,
                ]);
            }

            $updated = $this->transferRequestRepository->update($id, [
                'status'        => TransferRequest::STATUS_RECEIVED,
                'received_by'   => $receivedBy,
                'received_at'   => now(),
                'received_date' => $transDate->toDateString(),
            ]);

            $this->notifyUsers(
                $this->requesterUser($request),
                new TransferRequestNotification($request, TransferRequestNotification::RECEIVED)
            );

            return $updated;
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

    private function buildAutoAllocation(TransferRequest $request, TransferRequestItem $trItem)
    {
        $result = $this->itemLocationService->allocateForTransfer(
            (int) $trItem->item_id,
            (int) $request->department_id,
            $this->sourceWarehouseIds($request->destination_warehouse_id),
            (float) $trItem->requested_perpackage,
            (float) $trItem->requested_package,
            true   // kunci — dipanggil dari approve()
        );

        if (! $result->isFulfilled()) {
            throw new \Exception(
                "Stok tidak mencukupi untuk {$trItem->item->item_no}. Kurang " .
                    (int) $result->shortage . " package. Tolak item ini atau minta pemohon merevisi."
            );
        }

        return $result->lines;
    }

    /**
     * Alokasi manual divalidasi ulang dari NOL terhadap stok terkini —
     * data dari form tidak dipercaya begitu saja.
     *
     * $manualAllocation: [item_location_id => jumlah_package, ...]
     */
    private function buildManualAllocation(TransferRequest $request, TransferRequestItem $trItem, array $manualAllocation)
    {
        $sourceIds  = $this->sourceWarehouseIds($request->destination_warehouse_id);
        $perPackage = (float) $trItem->requested_perpackage;

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

            if ((int) $lot->item_id !== (int) $trItem->item_id) {
                throw new \Exception("Lot #{$lotId} bukan untuk item yang sama.");
            }

            if ((int) $lot->demander_id !== (int) $request->department_id) {
                throw new \Exception("Lot #{$lotId} bukan milik department pemohon.");
            }

            if (! in_array((int) $lot->warehouse_id, $sourceIds, true)) {
                throw new \Exception("Lot #{$lotId} bukan berasal dari gudang IMC yang sah.");
            }

            if ((float) $lot->qty_perpackage !== $perPackage) {
                throw new \Exception(
                    "Lot #{$lotId} ukurannya tidak sesuai permintaan " .
                        number_format($perPackage, 2, ',', '.') . " kg."
                );
            }

            $availablePackage = floor((float) $lot->qty_weight / $perPackage);

            if ($package > $availablePackage) {
                throw new \Exception(
                    "Lot " . ($lot->receiving_lot ?? "#{$lot->id}") .
                        " hanya tersedia {$availablePackage} package utuh."
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
            throw new \Exception("Belum ada lot yang dipilih untuk {$trItem->item->item_no}.");
        }

        if ($totalPackage != (float) $trItem->requested_package) {
            throw new \Exception(
                "Total package untuk {$trItem->item->item_no} ({$totalPackage}) harus sama dengan yang diminta (" .
                    (int) $trItem->requested_package . ")."
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

       /* ================= NOTIFIKASI ================= */

    /**
     * Kirim notifikasi tanpa mengganggu transaksi utama.
     *
     * Kegagalan kirim (relasi null, tabel penuh, dll) tidak boleh
     * membatalkan pemotongan stok yang sudah benar — cukup dicatat
     * di log.
     */
    private function notifyUsers($users, TransferRequestNotification $notification): void
    {
        try {
            foreach ($users as $user) {
                $user->notify($notification);
            }
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim notifikasi transfer: ' . $e->getMessage());
        }
    }

    /** Approver IMC — diambil dari tabel transfer_approvers. */
    private function approverUsers()
    {
        return User::whereHas('transferApprover')->get();
    }

    /** Semua user di department tujuan. */
    private function departmentUsers(int $departmentId)
    {
        return User::where('department_id', $departmentId)->get();
    }

    private function requesterUser(TransferRequest $request)
    {
        return User::where('id', $request->requested_by)->get();
    }
}
