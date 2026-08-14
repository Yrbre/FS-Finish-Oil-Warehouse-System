@extends('layouts.template')

@php
    $statusBadge = [
        'new' => 'badge-secondary',
        'in_transit' => 'badge-primary',
        'received' => 'badge-success',
        'rejected' => 'badge-danger',
        'cancelled' => 'badge-light',
    ];
@endphp

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">
                        {{ $transferRequest->transfer_code }}
                        <span class="badge {{ $statusBadge[$transferRequest->status] ?? 'badge-secondary' }} ml-2">
                            {{ strtoupper($transferRequest->status) }}
                        </span>
                    </h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('transfer-requests.index') }}" class="btn btn-light btn-sm">
                        <span class="fe fe-arrow-left fe-16 mr-2"></span>Kembali
                    </a>
                </div>
            </div>

            {{-- Info umum --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-3">
                            <p class="small text-muted mb-1">Item</p>
                            <strong>{{ $transferRequest->item->item_desc }}</strong>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Jumlah Diminta</p>
                            <strong>{{ number_format((float) $transferRequest->requested_qty, 2, ',', '.') }}
                                {{ $transferRequest->item->item_uom }}</strong>
                        </div>
                        <div class="col-md-3 mb-3">
                            <p class="small text-muted mb-1">Gudang Tujuan</p>
                            <strong>{{ $transferRequest->destinationWarehouse->name }}</strong>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Harus Sampai</p>
                            <strong>{{ \Carbon\Carbon::parse($transferRequest->expected_date)->format('d-m-Y') }}</strong>
                        </div>
                        <div class="col-md-2 mb-3">
                            <p class="small text-muted mb-1">Requester</p>
                            <strong>{{ $transferRequest->requester->name }}</strong>
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

            {{-- Status: NEW — form alokasi (pre-filled FEFO, bisa diedit manual) --}}
            @if ($transferRequest->status === 'new')
                <form action="{{ route('transfer-requests.approve', $transferRequest->id) }}" method="POST"
                    id="approveForm">
                    @csrf

                    <div class="card shadow mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <strong class="card-title">Pilih Lot & Qty yang Dikirim</strong>
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
                                            <th>Vendor Lot</th>
                                            <th>Exp Date</th>
                                            <th class="text-right">Stok Tersedia</th>
                                            <th class="text-right" style="width: 160px;">Qty Diambil</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($availableLots['lots'] as $lot)
                                            @php $suggested = $availableLots['suggestions'][$lot->id] ?? 0; @endphp
                                            <tr>
                                                <td>{{ $lot->warehouse->name }}</td>
                                                <td>{{ $lot->vendor_lot ?? '-' }}</td>
                                                <td>{{ $lot->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                                <td class="text-right">
                                                    {{ number_format((float) $lot->qty_weight, 2, ',', '.') }}</td>
                                                <td>
                                                    <input type="number" step="0.01" min="0"
                                                        max="{{ $lot->qty_weight }}"
                                                        name="allocation[{{ $lot->id }}]"
                                                        class="form-control form-control-sm text-right allocation-input"
                                                        value="{{ old('allocation.' . $lot->id, $suggested) }}"
                                                        data-suggested="{{ $suggested }}"
                                                        data-max="{{ $lot->qty_weight }}">
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted py-3">Tidak ada stok
                                                    tersedia di gudang IMC.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    <tfoot>
                                        <tr class="font-weight-bold">
                                            <td colspan="4" class="text-right">Total Diambil</td>
                                            <td class="text-right" id="allocationTotal">0,00</td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        <div class="card-footer" id="allocationStatus">
                            {{-- diisi JS --}}
                        </div>
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
                                        <button type="submit" class="btn btn-success btn-approve" id="btnApprove">
                                            <span class="fe fe-check fe-16 mr-2"></span>Approve & Kirim
                                        </button>
                                    </div>
                                @endcan

                                @can('transfer-requests.reject')
                                    <button type="button" class="btn btn-danger btn-reject ml-2" data-toggle="modal"
                                        data-target="#rejectModal">
                                        <span class="fe fe-x fe-16 mr-2"></span>Reject
                                    </button>
                                @endcan

                                @if ($transferRequest->requested_by === auth()->id() && $transferRequest->isCancellable())
                                    <div class="ml-2">
                                        <button type="submit" form="cancelForm" class="btn btn-outline-secondary">
                                            <span class="fe fe-slash fe-16 mr-2"></span>Batalkan Request
                                        </button>
                                    </div>
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

                {{-- Modal reject --}}
                @can('transfer-requests.reject')
                    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form action="{{ route('transfer-requests.reject', $transferRequest->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak Permintaan Kirim Barang </h5>
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

            {{-- Status: IN_TRANSIT / RECEIVED — tampilkan breakdown lot yang sudah dikirim --}}
            @if (in_array($transferRequest->status, ['in_transit', 'received']))
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <strong class="card-title">Rincian Pengiriman</strong>
                        <span class="float-right small text-muted">
                            Disetujui oleh {{ $transferRequest->approver->name ?? '-' }} ·
                            {{ $transferRequest->approved_date?->format('d-m-Y') }}
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Gudang Asal</th>
                                        <th>Vendor Lot</th>
                                        <th>Exp Date</th>
                                        <th class="text-right">Qty Dikirim</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($transferRequest->details as $detail)
                                        <tr>
                                            <td>{{ $detail->sourceWarehouse->name }}</td>
                                            <td>{{ $detail->vendor_lot ?? '-' }}</td>
                                            <td>{{ $detail->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td class="text-right">
                                                {{ number_format((float) $detail->qty_taken, 2, ',', '.') }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="3" class="text-right">Total</td>
                                        <td class="text-right">
                                            {{ number_format($transferRequest->details->sum('qty_taken'), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

                @if ($transferRequest->status === 'in_transit')
                    @can('transfer-requests.receive')
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <form action="{{ route('transfer-requests.receive', $transferRequest->id) }}" method="POST">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label class="small text-muted">Tanggal Efektif Terima (opsional)</label>
                                        <input type="date" name="effective_date" class="form-control form-control-sm"
                                            style="max-width: 250px;">
                                    </div>
                                    <button type="submit" class="btn btn-success btn-receive">
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
            @endif

            {{-- Status: REJECTED --}}
            @if ($transferRequest->status === 'rejected')
                <div class="alert alert-danger">
                    <strong>Ditolak oleh {{ $transferRequest->rejecter->name ?? '-' }}</strong>
                    <p class="mb-0 mt-1">{{ $transferRequest->reject_reason }}</p>
                </div>
            @endif

            {{-- Status: CANCELLED --}}
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
        // Hitung total qty yang diambil secara live, dan validasi sebelum submit
        function updateAllocationSummary() {
            const requestedQty = {{ (float) $transferRequest->requested_qty }};
            let total = 0;

            document.querySelectorAll('.allocation-input').forEach(function(input) {
                const val = parseFloat(input.value) || 0;
                const max = parseFloat(input.dataset.max) || 0;

                // Cegah user mengetik lebih dari stok tersedia di lot itu
                if (val > max) {
                    input.value = max;
                    total += max;
                } else {
                    total += val;
                }
            });

            const totalEl = document.getElementById('allocationTotal');
            const statusEl = document.getElementById('allocationStatus');
            const btnApprove = document.getElementById('btnApprove');

            if (totalEl) {
                totalEl.textContent = total.toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                });
            }

            if (statusEl) {
                const diff = Math.round((total - requestedQty) * 100) / 100;

                if (diff === 0) {
                    statusEl.className = 'card-footer bg-success text-white';
                    statusEl.innerHTML =
                        '<span class="fe fe-check-circle fe-16 mr-2"></span>Total sudah sesuai permintaan, siap di-approve.';
                    if (btnApprove) btnApprove.disabled = false;
                } else if (diff < 0) {
                    statusEl.className = 'card-footer bg-warning text-dark';
                    statusEl.innerHTML = '<span class="fe fe-alert-triangle fe-16 mr-2"></span>Kurang ' +
                        Math.abs(diff).toLocaleString('id-ID', {
                            minimumFractionDigits: 2
                        }) + ' dari jumlah yang diminta.';
                    if (btnApprove) btnApprove.disabled = true;
                } else {
                    statusEl.className = 'card-footer bg-danger text-white';
                    statusEl.innerHTML = '<span class="fe fe-alert-triangle fe-16 mr-2"></span>Melebihi ' +
                        diff.toLocaleString('id-ID', {
                            minimumFractionDigits: 2
                        }) + ' dari jumlah yang diminta.';
                    if (btnApprove) btnApprove.disabled = true;
                }
            }
        }

        document.querySelectorAll('.allocation-input').forEach(function(input) {
            input.addEventListener('input', updateAllocationSummary);
        });

        // Tombol "Isi Ulang Saran FEFO" — kembalikan semua input ke nilai saran awal
        const btnReset = document.getElementById('btnResetFefo');
        if (btnReset) {
            btnReset.addEventListener('click', function() {
                document.querySelectorAll('.allocation-input').forEach(function(input) {
                    input.value = input.dataset.suggested;
                });
                updateAllocationSummary();
            });
        }

        document.addEventListener('DOMContentLoaded', updateAllocationSummary);

        // Konfirmasi sebelum submit aksi-aksi penting
        function confirmSubmit(selector, title, text, confirmText, color) {
            document.querySelectorAll(selector).forEach(function(form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const target = this;

                    Swal.fire({
                        title: title,
                        text: text,
                        icon: 'warning',
                        theme: 'dark',
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

        confirmSubmit('#approveForm', 'Approve Permintaan Kirim Barang ?',
            'Stok akan dikurangi dari gudang asal sesuai lot & qty yang dipilih.', 'Ya, approve', '#28a745');
        confirmSubmit('.form-cancel', 'Batalkan request ini?', 'Request akan dibatalkan dan tidak bisa diproses lagi.',
            'Ya, batalkan', '#6c757d');
    </script>
@endpush
