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

            {{-- Status: NEW — rekomendasi FEFO + approve/reject/cancel --}}
            @if ($transferRequest->status === 'new')
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <strong class="card-title">Rekomendasi Alokasi (FEFO Lintas Gudang)</strong>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-bordered mb-0">
                                <thead>
                                    <tr>
                                        <th>Gudang Asal</th>
                                        <th>Vendor Lot</th>
                                        <th>Exp Date</th>
                                        <th class="text-right">Diambil</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recommendation['allocation'] as $row)
                                        <tr>
                                            <td>{{ $row['item_location']->warehouse->name }}</td>
                                            <td>{{ $row['item_location']->vendor_lot ?? '-' }}</td>
                                            <td>{{ $row['item_location']->exp_date?->format('d-m-Y') ?? '-' }}</td>
                                            <td class="text-right">{{ number_format($row['qty_to_take'], 2, ',', '.') }}
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Tidak ada stok tersedia
                                                di gudang manapun.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                                <tfoot>
                                    <tr class="font-weight-bold">
                                        <td colspan="3" class="text-right">Total Teralokasi</td>
                                        <td class="text-right">
                                            {{ number_format(collect($recommendation['allocation'])->sum('qty_to_take'), 2, ',', '.') }}
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                    @if ($recommendation['is_fulfilled'])
                        <div class="card-footer bg-success text-white">
                            <span class="fe fe-check-circle fe-16 mr-2"></span>Stok mencukupi, siap di-approve.
                        </div>
                    @else
                        <div class="card-footer bg-danger text-white">
                            <span class="fe fe-alert-triangle fe-16 mr-2"></span>
                            Stok tidak mencukupi di seluruh gudang. Kurang:
                            {{ number_format($recommendation['remaining_qty'], 2, ',', '.') }}
                        </div>
                    @endif
                </div>

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="d-flex flex-wrap" style="gap: 0.5rem;">

                            @can('approve-transfer')
                                @if ($recommendation['is_fulfilled'])
                                    <form action="{{ route('transfer-requests.approve', $transferRequest->id) }}"
                                        method="POST" class="form-approve">
                                        @csrf
                                        <div class="form-group mb-2">
                                            <label class="small text-muted">Tanggal Efektif Kirim (opsional, untuk
                                                backdate)</label>
                                            <input type="date" name="effective_date" class="form-control form-control-sm"
                                                max="{{ now()->toDateString() }}">
                                        </div>
                                        <button type="submit" class="btn btn-success btn-approve">
                                            <span class="fe fe-check fe-16 mr-2"></span>Approve & Kirim
                                        </button>
                                    </form>
                                @endif

                                <button type="button" class="btn btn-danger btn-reject ml-2" data-toggle="modal"
                                    data-target="#rejectModal">
                                    <span class="fe fe-x fe-16 mr-2"></span>Reject
                                </button>
                            @endcan

                            @if ($transferRequest->requested_by === auth()->id() && $transferRequest->isCancellable())
                                <form action="{{ route('transfer-requests.cancel', $transferRequest->id) }}" method="POST"
                                    class="form-cancel ml-2">
                                    @csrf
                                    <button type="submit" class="btn btn-outline-secondary">
                                        <span class="fe fe-slash fe-16 mr-2"></span>Batalkan Request
                                    </button>
                                </form>
                            @endif

                        </div>
                    </div>
                </div>

                {{-- Modal reject --}}
                @can('approve-transfer')
                    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog">
                        <div class="modal-dialog" role="document">
                            <div class="modal-content">
                                <form action="{{ route('transfer-requests.reject', $transferRequest->id) }}" method="POST">
                                    @csrf
                                    <div class="modal-header">
                                        <h5 class="modal-title">Tolak Transfer Request</h5>
                                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
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
                    @can('receive-transfer')
                        <div class="card shadow mb-4">
                            <div class="card-body">
                                <form action="{{ route('transfer-requests.receive', $transferRequest->id) }}" method="POST">
                                    @csrf
                                    <div class="form-group mb-2">
                                        <label class="small text-muted">Tanggal Efektif Terima (opsional, untuk
                                            backdate)</label>
                                        <input type="date" name="effective_date" class="form-control form-control-sm"
                                            style="max-width: 250px;"
                                            min="{{ $transferRequest->approved_date?->toDateString() }}"
                                            max="{{ now()->toDateString() }}">
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
        // Konfirmasi sebelum submit aksi-aksi penting
        function confirmSubmit(form, title, text, confirmText, color) {
            $(form).on('submit', function(e) {
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
        }

        confirmSubmit('.form-approve', 'Approve transfer request?',
            'Stok akan dikurangi dari gudang asal sesuai rekomendasi FEFO.', 'Ya, approve', '#28a745');
        confirmSubmit('.form-cancel', 'Batalkan request ini?', 'Request akan dibatalkan dan tidak bisa diproses lagi.',
            'Ya, batalkan', '#6c757d');
    </script>
@endpush
