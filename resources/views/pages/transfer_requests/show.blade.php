@extends('layouts.template')

@php
    $statusBadge = [
        'new' => 'badge-secondary',
        'approved' => 'badge-info',
        'in_transit' => 'badge-primary',
        'received' => 'badge-success',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-light',
    ];

    $isNew = $transferRequest->status === 'new';
    $isRequester = $transferRequest->requested_by === auth()->id();
    $hasReceipt = (bool) $transferRequest->receiptOfGoods;
    $canApprove = auth()->user()->can('transfer-requests.approve') && auth()->user()->isTransferApprover();

    $canReject = auth()->user()->can('transfer-requests.reject') && auth()->user()->isTransferApprover();
    // Rekomendasi hanya dihitung saat masih new
    $recItems = collect($recommendation['items'] ?? [])->keyBy(fn($r) => $r['item']->id);
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-11">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">
                        {{ $transferRequest->transfer_code }}
                        <span class="badge {{ $statusBadge[$transferRequest->status] ?? 'badge-secondary' }} ml-2">
                            {{ strtoupper(str_replace('_', ' ', $transferRequest->status)) }}
                        </span>
                    </h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('transfer-requests.index') }}" class="btn btn-light btn-sm">
                        <span class="fe fe-arrow-left fe-16 mr-2"></span>Kembali
                    </a>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif

            {{-- ===== Header info ===== --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <p class="small text-muted mb-1">Gudang Tujuan</p>
                            <strong>{{ $transferRequest->destinationWarehouse->name }}</strong>
                            <br><small class="text-muted">{{ $transferRequest->destinationWarehouse->tag }}</small>
                        </div>
                        <div class="col-md-2 mb-2">
                            <p class="small text-muted mb-1">Harus Sampai</p>
                            <strong>{{ $transferRequest->expected_date?->format('d-m-Y') }}</strong>
                        </div>
                        <div class="col-md-3 mb-2">
                            <p class="small text-muted mb-1">Pemohon</p>
                            <strong>{{ $transferRequest->requester->name }}</strong>
                            <br><small class="text-muted">{{ $transferRequest->department->code ?? '-' }}</small>
                        </div>
                        <div class="col-md-2 mb-2">
                            <p class="small text-muted mb-1">Jumlah Item</p>
                            <strong>{{ $transferRequest->items->count() }} item</strong>
                            @if ($transferRequest->items->where('status', '!=', 'new')->count())
                                <br><small class="text-muted">
                                    {{ $transferRequest->activeItems()->count() }} aktif
                                </small>
                            @endif
                        </div>
                        <div class="col-md-2 mb-2">
                            @if ($transferRequest->approver)
                                <p class="small text-muted mb-1">Disetujui</p>
                                <strong>{{ $transferRequest->approver->name }}</strong>
                                <br><small class="text-muted">
                                    {{ $transferRequest->approved_date?->format('d-m-Y') }}
                                </small>
                            @endif
                        </div>
                    </div>
                    @if ($transferRequest->notes)
                        <p class="small text-muted mb-1 mt-2">Catatan</p>
                        <p class="mb-0">{{ $transferRequest->notes }}</p>
                    @endif
                </div>
            </div>

            {{-- ===== Form approve membungkus semua item ===== --}}
            @if ($isNew && $canApprove)
                <form action="{{ route('transfer-requests.approve', $transferRequest->id) }}" method="POST"
                    id="approveForm">
                    @csrf
            @endif

            {{-- ===== Daftar item ===== --}}
            @foreach ($transferRequest->items as $trItem)
                @php
                    $rec = $recItems[$trItem->id] ?? null;
                    $perPackage = (float) $trItem->requested_perpackage;
                    $isVoid = $trItem->isVoid();
                @endphp

                <div class="card shadow mb-3 {{ $isVoid ? 'opacity-50' : '' }}">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <div>
                            <strong>{{ $trItem->item->item_no }} — {{ $trItem->item->item_desc }}</strong>
                            <span class="badge {{ $statusBadge[$trItem->status] ?? 'badge-secondary' }} ml-2">
                                {{ strtoupper($trItem->status) }}
                            </span>
                            <br>
                            <small class="text-muted">
                                Diminta {{ (int) $trItem->requested_package }} package
                                &times; {{ number_format($perPackage, 2, ',', '.') }} kg
                                = {{ number_format((float) $trItem->requested_qty, 2, ',', '.') }} kg
                            </small>
                        </div>

                        <div>
                            {{-- Aksi saat item masih new --}}
                            @if ($trItem->isPending())
                                @if ($canReject)
                                    <button type="button" class="btn btn-sm btn-outline-danger btn-reject-item"
                                        data-id="{{ $trItem->id }}" data-name="{{ e($trItem->item->item_desc) }}">
                                        Tolak
                                    </button>
                                @endif

                                @if ($isRequester && auth()->user()->can('transfer-requests.cancel'))
                                    <button type="button" class="btn btn-sm btn-outline-secondary btn-cancel-item"
                                        data-id="{{ $trItem->id }}" data-name="{{ e($trItem->item->item_desc) }}">
                                        Batalkan
                                    </button>
                                @endif
                            @endif

                            {{-- Batal setelah approve — stok dikembalikan.
                                 Hanya IMC, dan hanya sebelum TTB terbit. --}}
                            @if ($trItem->isApproved() && !$hasReceipt && $canApprove)
                                <button type="button" class="btn btn-sm btn-outline-warning btn-cancel-approved"
                                    data-id="{{ $trItem->id }}" data-name="{{ e($trItem->item->item_desc) }}">
                                    Batalkan & Kembalikan Stok
                                </button>
                            @endif
                        </div>
                    </div>

                    {{-- Item ditolak/dibatalkan --}}
                    @if ($isVoid)
                        <div class="card-body py-2">
                            <span class="text-muted small">
                                @if ($trItem->status === 'rejected')
                                    Ditolak {{ $trItem->rejecter->name ?? '-' }}
                                    {{ $trItem->rejected_at?->format('d-m-Y H:i') }} —
                                    {{ $trItem->reject_reason }}
                                @else
                                    Dibatalkan {{ $trItem->canceller->name ?? '-' }}
                                    {{ $trItem->cancelled_at?->format('d-m-Y H:i') }}
                                    @if ($trItem->cancel_reason)
                                        — {{ $trItem->cancel_reason }}
                                    @endif
                                @endif
                            </span>
                        </div>

                        {{-- Item masih new: tabel pilih lot --}}
                    @elseif ($trItem->isPending() && $rec)
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Gudang Asal</th>
                                            <th>Receiving Lot</th>
                                            <th>Vendor Lot</th>
                                            <th>Exp Date</th>
                                            <th class="text-right">Tersedia</th>
                                            @if ($canApprove)
                                                <th class="text-right" style="width: 120px;">Ambil</th>
                                            @endif
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($rec['lots'] as $lot)
                                            @php
                                                $availablePkg =
                                                    $perPackage > 0 ? floor((float) $lot->qty_weight / $perPackage) : 0;
                                                $suggested = (int) ($rec['suggestions'][$lot->id] ?? 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $lot->warehouse->name }} - {{ $lot->warehouse->tag }}</td>
                                                <td>{{ $lot->receiving_lot ?? '-' }}</td>
                                                <td>{{ $lot->vendor_lot ?? '-' }}</td>
                                                <td>{{ $lot->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                                <td class="text-right">
                                                    {{ (int) $availablePkg }} pkg
                                                    <br><small class="text-muted">
                                                        {{ number_format((float) $lot->qty_weight, 2, ',', '.') }} kg
                                                    </small>
                                                </td>
                                                @if ($canApprove)
                                                    <td>
                                                        <input type="number" step="1" min="0"
                                                            max="{{ (int) $availablePkg }}"
                                                            name="allocation[{{ $trItem->id }}][{{ $lot->id }}]"
                                                            class="form-control form-control-sm text-right alloc-input"
                                                            value="{{ $suggested }}" data-item="{{ $trItem->id }}"
                                                            data-max="{{ (int) $availablePkg }}">
                                                    </td>
                                                @endif
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="{{ $canApprove ? 6 : 5 }}"
                                                    class="text-center text-muted py-3">
                                                    Tidak ada lot berukuran
                                                    {{ number_format($perPackage, 2, ',', '.') }} kg
                                                    milik department ini di gudang IMC.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        @if ($canApprove)
                            <div class="card-footer py-2 alloc-status" data-item="{{ $trItem->id }}"
                                data-requested="{{ (int) $trItem->requested_package }}"
                                data-perpackage="{{ $perPackage }}"></div>
                        @else
                            <div
                                class="card-footer py-2 {{ $rec['is_fulfilled'] ? 'bg-success text-white' : 'bg-warning text-dark' }}">
                                @if ($rec['is_fulfilled'])
                                    Stok mencukupi.
                                @else
                                    Stok kurang {{ (int) $rec['shortage'] }} package.
                                @endif
                            </div>
                        @endif

                        {{-- Item sudah approved: rincian pengiriman --}}
                    @else
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-sm table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Gudang Asal</th>
                                            <th>Receiving Lot</th>
                                            <th>Vendor Lot</th>
                                            <th>Exp Date</th>
                                            <th class="text-right">Package</th>
                                            <th class="text-right">Berat</th>
                                            <th class="text-right">Sisa di Asal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($trItem->details as $detail)
                                            <tr>
                                                <td>{{ $detail->sourceWarehouse->name }} -
                                                    {{ $detail->sourceWarehouse->tag }}</td>
                                                <td>{{ $detail->receiving_lot ?? '-' }}</td>
                                                <td>{{ $detail->vendor_lot ?? '-' }}</td>
                                                <td>{{ $detail->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                                <td class="text-right">{{ (int) $detail->package_taken }} pkg</td>
                                                <td class="text-right">
                                                    {{ number_format((float) $detail->qty_taken, 2, ',', '.') }} kg</td>
                                                <td class="text-right">
                                                    {{ (int) $detail->remaining_package }} pkg
                                                    <br><small class="text-muted">
                                                        {{ number_format((float) $detail->remaining_weight, 2, ',', '.') }}
                                                        kg
                                                    </small>
                                                </td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="4" class="text-right">Total</td>
                                            <td class="text-right">
                                                {{ (int) $trItem->details->sum('package_taken') }} pkg</td>
                                            <td class="text-right">
                                                {{ number_format($trItem->details->sum('qty_taken'), 2, ',', '.') }} kg
                                            </td>
                                            <td></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    @endif
                </div>
            @endforeach

            {{-- ===== Tombol approve ===== --}}
            @if ($isNew && $canApprove)
                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-group mb-2" style="max-width: 250px;">
                            <label class="small text-muted">Tanggal Efektif Kirim (opsional)</label>
                            <input type="date" name="effective_date" class="form-control form-control-sm">
                        </div>
                        <button type="submit" class="btn btn-success" id="btnApprove">
                            <span class="fe fe-check fe-16 mr-2"></span>Approve Semua Item Aktif
                        </button>
                        <small class="text-muted ml-2">
                            Item yang stoknya tidak cukup harus ditolak dulu.
                        </small>
                    </div>
                </div>
                </form>
            @endif

            {{-- ===== Tanda terima ===== --}}
            @if ($transferRequest->status === 'approved')
                @if (auth()->user()->canIssueReceipt())
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <strong class="card-title">Tanda Terima Barang</strong>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Stok sudah dipotong. Barang dianggap berangkat setelah tanda terima diterbitkan.
                            </p>
                            <form action="{{ route('transfer-requests.issue-receipt', $transferRequest->id) }}"
                                method="POST" enctype="multipart/form-data" id="receiptForm" target="_blank">
                                @csrf
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Tanggal Kirim <span class="text-danger">*</span></label>
                                        <input type="date" name="letter_date" class="form-control"
                                            value="{{ now()->toDateString() }}" required>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Foto Barang (opsional)</label>
                                        <input type="file" name="photo" class="form-control-file"
                                            accept="image/jpeg,image/png">
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <span class="fe fe-printer fe-16 mr-2"></span>Terbitkan Tanda Terima
                                </button>
                            </form>
                        </div>
                    </div>
                @else
                    <div class="alert alert-info">
                        Menunggu penerbitan tanda terima oleh petugas yang berwenang.
                    </div>
                @endif
            @endif

            @if ($hasReceipt)
                @php $rog = $transferRequest->receiptOfGoods; @endphp
                <div class="card shadow mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-muted mb-1">Tanda Terima Barang</p>
                            <strong>{{ $rog->letter_number }}</strong>
                            <span class="text-muted ml-2">
                                {{ $rog->letter_date?->format('d-m-Y') }} ·
                                {{ $rog->responsibility->name ?? '-' }} ·
                                dicetak {{ $transferRequest->print_count }}&times;
                            </span>
                        </div>
                        <a href="{{ route('transfer-requests.receipt', $transferRequest->id) }}" target="_blank"
                            class="btn btn-warning">
                            <span class="fe fe-printer fe-16 mr-2"></span>Cetak Ulang
                        </a>
                    </div>
                </div>
            @endif

            {{-- ===== Konfirmasi terima ===== --}}
            @if ($transferRequest->status === 'in_transit')
                @can('transfer-requests.receive')
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="{{ route('transfer-requests.receive', $transferRequest->id) }}" method="POST"
                                class="form-receive">
                                @csrf
                                <div class="form-group mb-2" style="max-width: 250px;">
                                    <label class="small text-muted">Tanggal Efektif Terima (opsional)</label>
                                    <input type="date" name="effective_date" class="form-control form-control-sm">
                                </div>
                                <button type="submit" class="btn btn-success">
                                    <span class="fe fe-check-circle fe-16 mr-2"></span>Konfirmasi Barang Sampai
                                </button>
                            </form>
                        </div>
                    </div>
                @endcan
            @endif

            @if ($transferRequest->status === 'received')
                <div class="alert alert-success">
                    Diterima oleh {{ $transferRequest->receiver->name ?? '-' }} pada
                    {{ $transferRequest->received_date?->format('d-m-Y') }}.
                </div>
            @endif

        </div>
    </div>

    {{-- Modal alasan — dipakai bersama untuk tolak & batal-approved --}}
    <div class="modal fade" id="reasonModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" id="reasonForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title" id="reasonTitle">Alasan</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2" id="reasonSubtitle"></p>
                        <div id="reasonWarning"></div>
                        <div class="form-group mb-0">
                            <label>Alasan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="reason" rows="3" required minlength="5"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger" id="reasonSubmit">Lanjutkan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Form batal item (tanpa alasan) --}}
    <form method="POST" id="cancelItemForm" class="d-none">@csrf</form>
@endsection

@push('scripts')
    <script>
        // ===== Ringkasan alokasi per item =====
        function updateAllocSummary(itemId) {
            const $inputs = $(`.alloc-input[data-item="${itemId}"]`);
            const $status = $(`.alloc-status[data-item="${itemId}"]`);

            if (!$status.length) return;

            const requested = parseInt($status.data('requested'));
            const perPackage = parseFloat($status.data('perpackage'));

            let total = 0;

            $inputs.each(function() {
                let val = parseInt($(this).val()) || 0;
                const max = parseInt($(this).data('max')) || 0;

                if (val > max) {
                    val = max;
                    $(this).val(max);
                }
                if (val < 0) {
                    val = 0;
                    $(this).val(0);
                }

                total += val;
            });

            const weight = (total * perPackage).toLocaleString('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            const diff = total - requested;

            if (diff === 0) {
                $status.attr('class', 'card-footer py-2 alloc-status bg-success text-white')
                    .html(`Dipilih ${total} pkg (${weight} kg) — sesuai permintaan.`);
            } else if (diff < 0) {
                $status.attr('class', 'card-footer py-2 alloc-status bg-warning text-dark')
                    .html(`Dipilih ${total} pkg — kurang ${Math.abs(diff)} pkg dari ${requested} pkg yang diminta.`);
            } else {
                $status.attr('class', 'card-footer py-2 alloc-status bg-danger text-white')
                    .html(`Dipilih ${total} pkg — melebihi ${diff} pkg dari ${requested} pkg yang diminta.`);
            }

            // Approve hanya aktif kalau SEMUA item aktif sudah seimbang.
            let allOk = true;
            $('.alloc-status').each(function() {
                const id = $(this).data('item');
                let t = 0;
                $(`.alloc-input[data-item="${id}"]`).each(function() {
                    t += parseInt($(this).val()) || 0;
                });
                if (t !== parseInt($(this).data('requested'))) allOk = false;
            });

            $('#btnApprove').prop('disabled', !allOk);
        }

        $('.alloc-input').on('input', function() {
            updateAllocSummary($(this).data('item'));
        });

        $('.alloc-status').each(function() {
            updateAllocSummary($(this).data('item'));
        });

        // ===== Modal alasan =====
        function openReasonModal(action, title, subtitle, warning, btnText, btnClass) {
            $('#reasonForm').attr('action', action).find('textarea').val('');
            $('#reasonTitle').text(title);
            $('#reasonSubtitle').text(subtitle);
            $('#reasonWarning').html(warning);
            $('#reasonSubmit').text(btnText).attr('class', 'btn ' + btnClass);
            $('#reasonModal').modal('show');
        }

        $('.btn-reject-item').on('click', function() {
            openReasonModal(
                `/transfer-request-items/${$(this).data('id')}/reject`,
                'Tolak Item',
                $(this).data('name'),
                '',
                'Tolak Item',
                'btn-danger'
            );
        });

        $('.btn-cancel-approved').on('click', function() {
            openReasonModal(
                `/transfer-request-items/${$(this).data('id')}/cancel-approved`,
                'Batalkan Item yang Sudah Disetujui',
                $(this).data('name'),
                '<div class="alert alert-warning py-2 small">Stok akan dikembalikan ke lot asal di gudang IMC, ' +
                'dan catatan pengeluarannya dihapus.</div>',
                'Batalkan & Kembalikan Stok',
                'btn-warning'
            );
        });

        // ===== Batalkan item (pemohon, tanpa alasan) =====
        $('.btn-cancel-item').on('click', function() {
            const id = $(this).data('id');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Batalkan item ini?',
                text: name,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, batalkan',
                cancelButtonText: 'Tidak',
                confirmButtonColor: '#6c757d',
                reverseButtons: true,
            }).then((r) => {
                if (r.isConfirmed) {
                    $('#cancelItemForm').attr('action', `/transfer-request-items/${id}/cancel`).submit();
                }
            });
        });

        // ===== Konfirmasi form lain =====
        function confirmSubmit(selector, title, text, confirmText, color) {
            document.querySelectorAll(selector).forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const target = this;

                    Swal.fire({
                        title,
                        text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Batal',
                        confirmButtonColor: color,
                        reverseButtons: true,
                    }).then((r) => {
                        if (r.isConfirmed) target.submit();
                    });
                });
            });
        }

        confirmSubmit('#approveForm', 'Approve permintaan ini?',
            'Stok akan dipotong dari lot yang dipilih untuk semua item aktif.', 'Ya, approve', '#28a745');
        confirmSubmit('.form-receive', 'Konfirmasi barang sampai?',
            'Stok akan masuk ke gudang tujuan.', 'Ya, terima', '#28a745');

        // Tanda terima dibuka di tab baru, halaman ini di-refresh.
        const receiptForm = document.getElementById('receiptForm');
        if (receiptForm) {
            receiptForm.addEventListener('submit', function(e) {
                e.preventDefault();
                const target = this;

                Swal.fire({
                    title: 'Terbitkan tanda terima?',
                    text: 'Nomor surat akan diterbitkan dan status berubah menjadi dalam perjalanan.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonText: 'Ya, terbitkan',
                    cancelButtonText: 'Batal',
                    confirmButtonColor: '#007bff',
                    reverseButtons: true,
                }).then((r) => {
                    if (!r.isConfirmed) return;

                    target.submit();

                    Swal.fire({
                        title: 'Memproses...',
                        text: 'Tanda terima dibuka di tab baru.',
                        allowOutsideClick: false,
                        didOpen: () => Swal.showLoading(),
                    });

                    setTimeout(() => window.location.reload(), 1500);
                });
            });
        }
    </script>
@endpush
