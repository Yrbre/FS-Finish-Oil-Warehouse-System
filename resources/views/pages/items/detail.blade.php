@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Kartu Stok — {{ $item->item_desc }}</h2>
                    <p class="text-muted mb-0">{{ $item->item_no }} · Satuan {{ $item->item_uom }}</p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('items.index') }}" class="btn btn-light btn-sm">
                        <span class="fe fe-arrow-left fe-16 mr-2"></span>Kembali
                    </a>
                </div>
            </div>

            {{-- Filter --}}
            <div class="card shadow mb-4">
                <div class="card-body">
                    <form method="GET" action="{{ route('items.detail', $item->id) }}" class="form-row align-items-end">
                        <div class="col-md-3">
                            <label for="month" class="small text-muted">Bulan</label>
                            <select name="month" id="month" class="form-control">
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>
                                        {{ \Carbon\Carbon::create()->month($m)->translatedFormat('F') }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="year" class="small text-muted">Tahun</label>
                            <select name="year" id="year" class="form-control">
                                @foreach (range(now()->year - 3, now()->year + 1) as $y)
                                    <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>
                                        {{ $y }}</option>
                                @endforeach
                            </select>
                        </div>

                        @if ($isFullAccess)
                            <div class="col-md-3">
                                <label for="department_id" class="small text-muted">Department</label>
                                <select name="department_id" id="department_id" class="form-control select2">
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ $departmentId == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->code }} - {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label for="warehouse_id" class="small text-muted">Gudang <small>(opsional)</small></label>
                                <select name="warehouse_id" id="warehouse_id" class="form-control select2">
                                    <option value="">Semua gudang di department</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}" data-department="{{ $wh->department_id }}"
                                            {{ $warehouseId == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <div class="col-md-5">
                                <label class="small text-muted">Scope</label>
                                <p class="form-control-plaintext">
                                    <span class="">Gudang department Anda</span><br>
                                </p>
                            </div>
                        @endif

                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary btn-block">
                                <span class="fe fe-filter fe-16 mr-2"></span>Tampilkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Ringkasan --}}
            <div class="row">
                <div class="col-md-4 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <p class="mb-1 small text-muted text-uppercase">Total Masuk</p>
                            <span class="h4 text-success">{{ number_format($summary->total_in, 2, ',', '.') }}</span>
                            <span class="text-muted">{{ $item->item_uom }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <p class="mb-1 small text-muted text-uppercase">Total Keluar</p>
                            <span class="h4 text-danger">{{ number_format($summary->total_out, 2, ',', '.') }}</span>
                            <span class="text-muted">{{ $item->item_uom }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <p class="mb-1 small text-muted text-uppercase">Saldo Akhir</p>
                            <span class="h4">{{ number_format($summary->closing, 2, ',', '.') }}</span>
                            <span class="text-muted">{{ $item->item_uom }}</span>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Tabel kartu stok --}}
            <div class="card shadow">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover">
                            <thead>
                                <tr>
                                    <th>Tanggal</th>
                                    <th class="text-right">Saldo Awal</th>
                                    <th class="text-right">Masuk</th>
                                    <th class="text-right">Keluar</th>
                                    <th class="text-right">Adjustment</th>
                                    <th class="text-right">Saldo Akhir</th>
                                    <th>Jenis</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($stockCard as $row)
                                    <tr class="{{ $row->has_trx ? '' : 'text-muted' }}">
                                        <td>{{ \Carbon\Carbon::parse($row->trans_date)->format('d-m-Y') }}</td>
                                        <td class="text-right">{{ number_format($row->bb_qty, 2, ',', '.') }}</td>
                                        <td class="text-right text-success">
                                            {{ $row->in_qty > 0 ? number_format($row->in_qty, 2, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-right text-danger">
                                            {{ $row->out_qty > 0 ? number_format($row->out_qty, 2, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-right">
                                            {{ $row->adj_qty != 0 ? number_format($row->adj_qty, 2, ',', '.') : '-' }}
                                        </td>
                                        <td class="text-right font-weight-bold">
                                            {{ number_format($row->eb_qty, 2, ',', '.') }}
                                        </td>
                                        <td><small>{{ $row->doc_type ?? '-' }}</small></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Tampilkan hanya gudang milik department yang dipilih
        function filterWarehousesByDepartment() {
            const deptId = $('#department_id').val();

            $('#warehouse_id option').each(function() {
                const optDept = $(this).data('department');
                // Opsi "Semua gudang di department" (tanpa data-department) selalu tampil
                if (typeof optDept === 'undefined') return;

                $(this).toggle(String(optDept) === String(deptId));
            });
        }

        $('#department_id').on('change', function() {
            // Reset pilihan gudang spesifik saat department diganti,
            // supaya tidak nyangkut ke gudang department lain
            $('#warehouse_id').val('').trigger('change');
            filterWarehousesByDepartment();
        });

        $(document).ready(filterWarehousesByDepartment);
    </script>
@endpush
