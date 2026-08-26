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

    $perPackage = (float) $transferRequest->requested_perpackage;
    $reqPackage = (float) $transferRequest->requested_package;
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

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

            {{-- Info umum --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <p class="small text-muted mb-1">Item</p>
                            <strong>{{ $transferRequest->item->item_desc }}</strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <p class="small text-muted mb-1">Diminta</p>
                            <strong>{{ (int) $reqPackage }} package
                                &times; {{ number_format($perPackage, 2, ',', '.') }} kg</strong>
                            <br>
                            <small class="text-muted">
                                setara {{ number_format((float) $transferRequest->requested_qty, 2, ',', '.') }}
                                {{ $transferRequest->item->item_uom }}
                            </small>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Gudang Tujuan</p>
                            <strong>{{ $transferRequest->destinationWarehouse->name }}</strong>
                            <br><small class="text-muted">{{ $transferRequest->destinationWarehouse->tag }}</small>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Harus Sampai</p>
                            <strong>{{ $transferRequest->expected_date?->format('d-m-Y') }}</strong>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Pemohon</p>
                            <strong>{{ $transferRequest->requester->name }}</strong>
                            <br><small class="text-muted">{{ $transferRequest->department->code ?? '-' }}</small>
                        </div>
                    </div>
                    @if ($transferRequest->notes)
                        <div class="row">
                            <div class="col-12">
                                <p class="small text-muted mb-1">Catatan</p>
                                <p class="mb-0">{{ $transferRequest->notes }}</p>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- ================= STATUS: NEW ================= --}}
            @if ($transferRequest->status === 'new')
                @php
                    $lots = $recommendation['lots'] ?? collect();
                    $suggestions = $recommendation['suggestions'] ?? [];
                @endphp

                <form action="{{ route('transfer-requests.approve', $transferRequest->id) }}" method="POST"
                    id="approveForm">
                    @csrf

                    <div class="card shadow mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <strong class="card-title">Pilih Lot yang Dikirim</strong>
                                <br>
                                <small class="text-muted">
                                    Lot milik {{ $transferRequest->department->code ?? '-' }} di gudang IMC,
                                    ukuran {{ number_format($perPackage, 2, ',', '.') }} kg.
                                    Sudah diisi saran FEFO — boleh diubah.
                                </small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="btnResetFefo">
                                <span class="fe fe-refresh-cw fe-14 mr-1"></span>Isi Ulang Saran FEFO
                            </button>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-bordered mb-0">
                                    <thead>
                                        <tr>
                                            <th>Gudang Asal</th>
                                            <th>Receiving Lot</th>
                                            <th>Vendor Lot</th>
                                            <th>Exp Date</th>
                                            <th class="text-right">Tersedia</th>
                                            <th class="text-right" style="width: 140px;">Package Diambil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($lots as $lot)
                                            @php
                                                // Hanya package UTUH yang boleh keluar dari IMC.
                                                $availablePkg =
                                                    $perPackage > 0 ? floor((float) $lot->qty_weight / $perPackage) : 0;
                                                $suggested = (int) ($suggestions[$lot->id] ?? 0);
                                            @endphp
                                            <tr>
                                                <td>{{ $lot->warehouse->name }} - {{ $lot->warehouse->tag }}</td>
                                                <td>{{ $lot->receiving_lot ?? '-' }}</td>
                                                <td>{{ $lot->vendor_lot ?? '-' }}</td>
                                                <td>{{ $lot->exp_date?->format('M Y') ?? '-' }}</td>
                                                <td class="text-right">
                                                    {{ (int) $availablePkg }} pkg
                                                    <br>
                                                    <small class="text-muted">
                                                        {{ number_format((float) $lot->qty_weight, 2, ',', '.') }} kg
                                                    </small>
                                                </td>
                                                <td>
                                                    <input type="number" step="1" min="0"
                                                        max="{{ (int) $availablePkg }}"
                                                        name="allocation[{{ $lot->id }}]"
                                                        class="form-control form-control-sm text-right allocation-input"
                                                        value="{{ old('allocation.' . $lot->id, $suggested) }}"
                                                        data-suggested="{{ $suggested }}"
                                                        data-max="{{ (int) $availablePkg }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">
                                                    Tidak ada lot berukuran
                                                    {{ number_format($perPackage, 2, ',', '.') }} kg
                                                    milik department ini di gudang IMC.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="4" class="text-right">Total Dipilih</td>
                                            <td class="text-right" id="allocationWeight">0,00 kg</td>
                                            <td class="text-right" id="allocationTotal">0 pkg</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer" id="allocationStatus"></div>
                    </div>

                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <div class="d-flex flex-wrap align-items-end" style="gap: 0.5rem;">

                                @can('transfer-requests.approve')
                                    <div>
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Tanggal Efektif Kirim (opsional)</label>
                                            <input type="date" name="effective_date" class="form-control form-control-sm">
                                        </div>
                                        <button type="submit" class="btn btn-success" id="btnApprove" disabled>
                                            <span class="fe fe-check fe-16 mr-2"></span>Approve
                                        </button>
                                    </div>
                                @endcan

                                @can('transfer-requests.reject')
                                    <button type="button" class="btn btn-danger ml-2" data-toggle="modal"
                                        data-target="#rejectModal">
                                        <span class="fe fe-x fe-16 mr-2"></span>Reject
                                    </button>
                                @endcan

                                @if ($transferRequest->requested_by === auth()->id() && $transferRequest->isCancellable())
                                    <button type="submit" form="cancelForm" class="btn btn-outline-secondary ml-2">
                                        <span class="fe fe-slash fe-16 mr-2"></span>Batalkan Request
                                    </button>
                                @endif
                            </div>
                        </div>
                    </div>
                </form>

                @if ($transferRequest->requested_by === auth()->id() && $transferRequest->isCancellable())
                    <form action="{{ route('transfer-requests.cancel', $transferRequest->id) }}" method="POST"
                        id="cancelForm" class="form-cancel d-none">
                        @csrf
                    </form>
                @endif

                @can('transfer-requests.reject')
                    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form action="{{ route('transfer-requests.reject', $transferRequest->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak Permintaan Kirim Barang</h5>
                                        <button type="button" class="close"
                                            data-dismiss="modal"><span>&times;</span></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="form-group">
                                            <label>Alasan Penolakan <span class="text-danger">*</span></label>
                                            <textarea class="form-control" name="reject_reason" rows="3" required></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                                        <button type="submit" class="btn btn-danger">Tolak Request</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                @endcan
            @endif

            {{-- ========== STATUS: APPROVED / IN_TRANSIT / RECEIVED ========== --}}
            @if (in_array($transferRequest->status, ['approved', 'in_transit', 'received']))
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <strong class="card-title">Rincian Pengiriman</strong>
                        <span class="float-right small text-muted">
                            Disetujui {{ $transferRequest->approver->name ?? '-' }} ·
                            {{ $transferRequest->approved_date?->format('d-m-Y') }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Gudang Asal</th>
                                        <th>Receiving Lot</th>
                                        <th>Vendor Lot</th>
                                        <th>Exp Date</th>
                                        <th class="text-right">Package</th>
                                        <th class="text-right">Berat</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transferRequest->details as $detail)
                                        <tr>
                                            <td>{{ $detail->sourceWarehouse->name }} -
                                                {{ $detail->sourceWarehouse->tag }}</td>
                                            <td>{{ $detail->receiving_lot ?? '-' }}</td>
                                            <td>{{ $detail->vendor_lot ?? '-' }}</td>
                                            <td>{{ $detail->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td class="text-right">{{ (int) $detail->package_taken }} pkg</td>
                                            <td class="text-right">
                                                {{ number_format((float) $detail->qty_taken, 2, ',', '.') }} kg</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="4" class="text-right">Total</td>
                                        <td class="text-right">
                                            {{ (int) $transferRequest->details->sum('package_taken') }} pkg</td>
                                        <td class="text-right">
                                            {{ number_format($transferRequest->details->sum('qty_taken'), 2, ',', '.') }}
                                            kg</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            @endif

            {{-- STATUS: APPROVED — buat tanda terima --}}
            @if ($transferRequest->status === 'approved')
                @if (auth()->user()->canIssueReceipt())
                    <div class="card shadow mb-4">
                        <div class="card-header">
                            <strong class="card-title">Tanda Terima Barang</strong>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Stok sudah dipotong dari gudang asal. Barang dianggap berangkat
                                setelah tanda terima diterbitkan.
                            </p>
                            <form action="{{ route('transfer-requests.issue-receipt', $transferRequest->id) }}"
                                method="POST" enctype="multipart/form-data" id="receiptForm">
                                @csrf
                                <div class="form-row">
                                    <div class="form-group col-md-4">
                                        <label>Tanggal Kirim <span class="text-danger">*</span></label>
                                        <input type="date" name="letter_date" class="form-control"
                                            value="{{ now()->toDateString() }}" required>
                                        <small class="form-text text-muted">Tanggal yang tercetak di dokumen.</small>
                                    </div>
                                    <div class="form-group col-md-5">
                                        <label>Foto Barang (opsional)</label>
                                        <input type="file" name="photo" class="form-control-file"
                                            accept="image/jpeg,image/png">
                                        <small class="form-text text-muted">JPG/PNG, maks 2 MB.</small>
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
                        <span class="fe fe-info fe-16 mr-2"></span>
                        Menunggu penerbitan tanda terima barang oleh petugas yang berwenang.
                    </div>
                @endif
            @endif

            {{-- Info tanda terima yang sudah terbit --}}
            @if ($transferRequest->receiptOfGoods)
                @php $rog = $transferRequest->receiptOfGoods; @endphp
                <div class="card shadow mb-4">
                    <div class="card-body d-flex justify-content-between align-items-center">
                        <div>
                            <p class="small text-muted mb-1">Tanda Terima Barang</p>
                            <strong>{{ $rog->letter_number }}</strong>
                            <span class="text-muted ml-2">
                                {{ $rog->letter_date?->format('d-m-Y') }} ·
                                dibuat {{ $rog->responsibility->name ?? '-' }} ·
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

            {{-- STATUS: IN_TRANSIT — konfirmasi terima --}}
            @if ($transferRequest->status === 'in_transit')
                @can('transfer-requests.receive')
                    <div class="card shadow mb-4">
                        <div class="card-body">
                            <form action="{{ route('transfer-requests.receive', $transferRequest->id) }}" method="POST"
                                class="form-receive">
                                @csrf
                                <div class="form-group mb-2">
                                    <label class="small text-muted">Tanggal Efektif Terima (opsional)</label>
                                    <input type="date" name="effective_date" class="form-control form-control-sm"
                                        style="max-width: 250px;">
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
                    <span class="fe fe-check-circle fe-16 mr-2"></span>
                    Diterima oleh {{ $transferRequest->receiver->name ?? '-' }} pada
                    {{ $transferRequest->received_date?->format('d-m-Y') }}.
                </div>
            @endif

            @if ($transferRequest->status === 'rejected')
                <div class="alert alert-danger">
                    <strong>Ditolak oleh {{ $transferRequest->rejecter->name ?? '-' }}</strong>
                    <p class="mb-0 mt-1">{{ $transferRequest->reject_reason }}</p>
                </div>
            @endif

            @if ($transferRequest->status === 'cancelled')
                <div class="alert alert-secondary">
                    Dibatalkan oleh {{ $transferRequest->canceller->name ?? '-' }} pada
                    {{ $transferRequest->cancelled_at?->format('d-m-Y H:i') }}.
                </div>
            @endif

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        const REQUESTED_PACKAGE = {{ (int) $reqPackage }};
        const PER_PACKAGE = {{ $perPackage }};

        function updateAllocationSummary() {
            let totalPkg = 0;

            document.querySelectorAll('.allocation-input').forEach(function(input) {
                let val = parseInt(input.value) || 0;
                const max = parseInt(input.dataset.max) || 0;

                // Tidak boleh melebihi package utuh yang tersedia di lot itu.
                if (val > max) {
                    val = max;
                    input.value = max;
                }
                if (val < 0) {
                    val = 0;
                    input.value = 0;
                }

                totalPkg += val;
            });

            const totalEl = document.getElementById('allocationTotal');
            const weightEl = document.getElementById('allocationWeight');
            const statusEl = document.getElementById('allocationStatus');
            const btnApprove = document.getElementById('btnApprove');

            if (!statusEl) return;

            const totalWeight = totalPkg * PER_PACKAGE;

            if (totalEl) totalEl.textContent = totalPkg + ' pkg';
            if (weightEl) {
                weightEl.textContent = totalWeight.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' kg';
            }

            const diff = totalPkg - REQUESTED_PACKAGE;

            if (diff === 0) {
                statusEl.className = 'card-footer bg-success text-white';
                statusEl.innerHTML =
                    '<span class="fe fe-check-circle fe-16 mr-2"></span>Total sesuai permintaan, siap di-approve.';
                if (btnApprove) btnApprove.disabled = false;
            } else if (diff < 0) {
                statusEl.className = 'card-footer bg-warning text-dark';
                statusEl.innerHTML = '<span class="fe fe-alert-triangle fe-16 mr-2"></span>Kurang ' +
                    Math.abs(diff) + ' package dari yang diminta (' + REQUESTED_PACKAGE + ' pkg).';
                if (btnApprove) btnApprove.disabled = true;
            } else {
                statusEl.className = 'card-footer bg-danger text-white';
                statusEl.innerHTML = '<span class="fe fe-alert-triangle fe-16 mr-2"></span>Melebihi ' +
                    diff + ' package dari yang diminta (' + REQUESTED_PACKAGE + ' pkg).';
                if (btnApprove) btnApprove.disabled = true;
            }
        }

        document.querySelectorAll('.allocation-input').forEach(function(input) {
            input.addEventListener('input', updateAllocationSummary);
        });

        const btnReset = document.getElementById('btnResetFefo');
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                document.querySelectorAll('.allocation-input').forEach(function(input) {
                    input.value = input.dataset.suggested;
                });
                updateAllocationSummary();
            });
        }

        updateAllocationSummary();

        function confirmSubmit(selector, title, text, confirmText, color) {
            document.querySelectorAll(selector).forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const target = this;

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: confirmText,
                        cancelButtonText: 'Batal',
                        confirmButtonColor: color,
                        reverseButtons: true,
                    }).then((result) => {
                        if (result.isConfirmed) target.submit();
                    });
                });
            });
        }

        confirmSubmit('#approveForm', 'Approve permintaan ini?',
            'Stok akan dipotong dari lot yang dipilih.', 'Ya, approve', '#28a745');
        confirmSubmit('#receiptForm', 'Terbitkan tanda terima?',
            'Nomor surat akan diterbitkan dan status berubah menjadi dalam perjalanan.', 'Ya, terbitkan', '#007bff');
        confirmSubmit('.form-receive', 'Konfirmasi barang sampai?',
            'Stok akan masuk ke gudang tujuan.', 'Ya, terima', '#28a745');
        confirmSubmit('.form-cancel', 'Batalkan request ini?',
            'Request akan dibatalkan dan tidak bisa diproses lagi.', 'Ya, batalkan', '#6c757d');
    </script>
@endpush
