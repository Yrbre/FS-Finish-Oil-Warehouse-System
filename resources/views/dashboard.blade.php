@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Dashboard</h2>
                    <p class="text-muted mb-0">Selamat datang, {{ auth()->user()->name }}.</p>
                </div>
            </div>

            {{-- ===== Widget ringkasan ===== --}}
            <div class="row mt-3">
                <div class="col-md-3 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <p class="small text-muted mb-1 text-uppercase">Total Item</p>
                            <span class="h3">{{ $summary->total_items }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            <p class="small text-muted mb-1 text-uppercase">Total Gudang</p>
                            <span class="h3">{{ $summary->total_warehouses }}</span>
                        </div>
                    </div>
                </div>
                <div class="col-md-3 mb-4">
                    <div class="card shadow">
                        <div class="card-body">
                            {{-- Label berbeda untuk staff: angkanya hanya
                                 stok miliknya, bukan seluruh department. --}}
                            <p class="small text-muted mb-1 text-uppercase">{{ $summary->stock_label }}</p>
                            <span class="h3">{{ number_format($summary->total_stock, 0, ',', '.') }}</span>
                        </div>
                    </div>
                </div>
                @can('transfer-requests.approve')
                    <div class="col-md-3 mb-4">
                        <div class="card shadow {{ $pendingApproval > 0 ? 'border-warning' : '' }}">
                            <div class="card-body">
                                <p class="small text-muted mb-1 text-uppercase">Menunggu Approval</p>
                                <span class="h3 {{ $pendingApproval > 0 ? 'text-warning' : '' }}">{{ $pendingApproval }}</span>
                                @if ($pendingApproval > 0)
                                    <a href="{{ route('transfer-requests.index') }}?status=new" class="small d-block">Lihat
                                        semua &raquo;</a>
                                @endif
                            </div>
                        </div>
                    </div>
                @else
                    <div class="col-md-3 mb-4">
                        <div class="card shadow">
                            <div class="card-body">
                                <p class="small text-muted mb-1 text-uppercase">Request Saya Berjalan</p>
                                <span class="h3">{{ $myOpenRequests }}</span>
                            </div>
                        </div>
                    </div>
                @endcan
            </div>



            <div class="row">
                {{-- ===== Transaksi terbaru ===== --}}
                <div class="col-md-12 col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <strong class="card-title">Transaksi Terbaru</strong>
                            @canany(['transactions.porc.view', 'transactions.cons.view', 'transactions.adj.view'])
                                <a href="{{ route('transactions.index') }}" class="float-right small text-muted">Lihat
                                    semua</a>
                            @endcanany
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Item</th>
                                        <th>Gudang</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentTransactions as $trx)
                                        <tr>
                                            <td>{{ \Carbon\Carbon::parse($trx->trans_date)->format('d-m-Y') }}</td>
                                            <td><span class="badge badge-light">{{ $trx->doc_type }}</span></td>
                                            <td>{{ $trx->item_desc }}</td>
                                            <td>{{ $trx->warehouse->name ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Belum ada transaksi.
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- ===== Near expiry ===== --}}
                <div class="col-md-12 col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <strong class="card-title">Stok Mendekati Expired (30 hari)</strong>
                            @can('reports.view')
                                <a href="{{ route('reports.index') }}" class="float-right small text-muted">Lihat semua</a>
                            @endcan
                        </div>
                        <div class="card-body p-0">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th>Gudang</th>
                                        <th>Pemilik</th>
                                        <th>Exp Date</th>
                                        <th class="text-right">Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($nearExpiry as $lot)
                                        @php $daysLeft = now()->diffInDays($lot->exp_date, false); @endphp
                                        <tr
                                            class="{{ $daysLeft < 0 ? 'table-danger' : ($daysLeft <= 7 ? 'table-warning' : '') }}">
                                            <td class="text-dark">{{ $lot->item->item_desc }}</td>
                                            <td class="text-dark">{{ $lot->warehouse->name }}</td>
                                            <td class="text-dark">{{ $lot->demander->code ?? '-' }}</td>
                                            <td class="text-dark">{{ $lot->exp_date->format('d-m-Y') }}</td>
                                            <td class="text-right text-dark">
                                                {{ number_format((float) $lot->qty_weight, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-3">
                                                Tidak ada stok yang mendekati expired.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            {{-- ===== Rekap transfer selesai (khusus IMC) ===== --}}
            @role('imc')
                <div class="card shadow mb-4">
                    <div class="card-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <strong class="card-title">Rekap Transfer Selesai</strong>
                                <br><small class="text-muted">Berdasarkan tanggal barang diterima.</small>
                            </div>
                            <div class="col-auto">
                                <div class="btn-group btn-group-sm" role="group">
                                    <button type="button" class="btn btn-outline-secondary btn-range" data-range="today">Hari
                                        Ini</button>
                                    <button type="button" class="btn btn-outline-secondary btn-range" data-range="month">Bulan
                                        Ini</button>
                                    <button type="button" class="btn btn-outline-secondary btn-range active"
                                        data-range="year">Tahun Ini</button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="form-row mb-3">
                            <div class="col-md-3">
                                <label class="small text-muted">Dari</label>
                                <input type="date" id="sumFrom" class="form-control form-control-sm"
                                    value="{{ now()->startOfYear()->toDateString() }}">
                            </div>
                            <div class="col-md-3">
                                <label class="small text-muted">Sampai</label>
                                <input type="date" id="sumTo" class="form-control form-control-sm"
                                    value="{{ now()->toDateString() }}">
                            </div>
                            <div class="col-md-2 d-flex align-items-end">
                                <button type="button" class="btn btn-sm btn-primary" id="btnLoadSummary">
                                    <span class="fe fe-search fe-16 mr-1"></span>Tampilkan
                                </button>
                            </div>
                        </div>

                        <div class="row text-center mb-4">
                            <div class="col-md-3">
                                <p class="small text-muted mb-1 text-uppercase">Request Selesai</p>
                                <span class="h4" id="statRequest">-</span>
                            </div>
                            <div class="col-md-3">
                                <p class="small text-muted mb-1 text-uppercase">Item Terkirim</p>
                                <span class="h4" id="statItem">-</span>
                            </div>
                            <div class="col-md-3">
                                <p class="small text-muted mb-1 text-uppercase">Total Package</p>
                                <span class="h4" id="statPackage">-</span>
                            </div>
                            <div class="col-md-3">
                                <p class="small text-muted mb-1 text-uppercase">Total Berat (KG)</p>
                                <span class="h4" id="statWeight">-</span>
                            </div>
                        </div>

                        <p class="small text-muted text-uppercase mb-2">Daftar Pengiriman</p>
                        <div id="sumRequestList">
                            <p class="text-muted text-center py-3 mb-0">Memuat...</p>
                        </div>
                    </div>
                </div>
            @endrole

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function fmt(n, dec = 0) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: dec,
                maximumFractionDigits: dec
            }).format(n);
        }

        function loadTransferSummary() {
            const from = $('#sumFrom').val();
            const to = $('#sumTo').val();

            if (!from || !to) return;

            $('#sumRequestList').html('<p class="text-muted text-center py-3 mb-0">Memuat...</p>');

            $.get('{{ route('dashboard.transfer-summary') }}', {
                from,
                to
            }).done(function(res) {
                $('#statRequest').text(fmt(res.total_request));
                $('#statItem').text(fmt(res.total_item));
                $('#statPackage').text(fmt(res.total_package));
                $('#statWeight').text(fmt(res.total_weight, 2));

                if (!res.requests.length) {
                    $('#sumRequestList').html(
                        '<p class="text-muted text-center py-3 mb-0">Tidak ada pengiriman pada rentang ini.</p>'
                    );
                    return;
                }

                let html = '<div class="list-group">';

                res.requests.forEach(function(r, idx) {
                    let rows = '';

                    r.items.forEach(function(it, i) {
                        rows += `<tr>
                            <td>${i + 1}</td>
                            <td><strong>${it.item_no}</strong><br>
                                <small class="text-muted">${it.item_desc}</small></td>
                            <td class="text-right">${fmt(it.package)} pkg<br>
                                <small class="text-muted">@ ${fmt(it.perpackage, 2)} kg</small></td>
                            <td class="text-right">${fmt(it.weight, 2)} ${it.uom}</td>
                        </tr>`;
                    });

                    const surat = r.letter_number ?
                        `<span class="badge badge-light ml-2">${r.letter_number}</span>` :
                        '';

                    html += `
                    <div class="list-group-item px-3 py-2">
                        <div class="d-flex justify-content-between align-items-center"
                             style="cursor:pointer" data-toggle="collapse" data-target="#trx${idx}">
                            <div>
                                <strong>${r.code}</strong>${surat}
                                <br>
                                <small class="text-muted">
                                    ${r.department} &rarr; ${r.destination}
                                    &middot; ${r.requester}
                                    &middot; ${r.received_date}
                                </small>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-success">${r.items.length} item</span>
                                <br>
                                <small class="text-muted">
                                    ${fmt(r.total_package)} pkg &middot; ${fmt(r.total_weight, 2)} kg
                                </small>
                            </div>
                        </div>

                        <div class="collapse mt-2" id="trx${idx}">
                            <table class="table table-sm table-bordered mb-2">
                                <thead>
                                    <tr>
                                        <th style="width:5%">#</th>
                                        <th>Item</th>
                                        <th class="text-right" style="width:20%">Package</th>
                                        <th class="text-right" style="width:20%">Berat</th>
                                    </tr>
                                </thead>
                                <tbody>${rows}</tbody>
                            </table>
                            <a href="${r.url}" class="btn btn-sm btn-outline-info">
                                <span class="fe fe-external-link fe-14 mr-1"></span>Buka Detail
                            </a>
                        </div>
                    </div>`;
                });

                html += '</div>';
                $('#sumRequestList').html(html);
            }).fail(function() {
                $('#sumRequestList').html(
                    '<p class="text-center text-danger py-3 mb-0">Gagal memuat data.</p>');
            });
        }

        // Tombol pintas mengisi tanggal lalu memuat ulang.
        $('.btn-range').on('click', function() {
            const range = $(this).data('range');
            const now = new Date();
            let from;

            if (range === 'today') {
                from = now;
            } else if (range === 'month') {
                from = new Date(now.getFullYear(), now.getMonth(), 1);
            } else {
                from = new Date(now.getFullYear(), 0, 1);
            }

            // Format manual dari waktu lokal — toISOString() mengonversi
            // ke UTC, jadi di WIB tanggalnya bisa mundur sehari.
            const iso = (d) => d.getFullYear() + '-' +
                String(d.getMonth() + 1).padStart(2, '0') + '-' +
                String(d.getDate()).padStart(2, '0');

            $('#sumFrom').val(iso(from));
            $('#sumTo').val(iso(now));

            $('.btn-range').removeClass('active');
            $(this).addClass('active');

            loadTransferSummary();
        });

        $('#btnLoadSummary').on('click', loadTransferSummary);

        $(document).ready(function() {
            // Widget hanya ada untuk role imc — cek dulu supaya user
            // lain tidak memicu AJAX yang akan ditolak 403.
            if ($('#sumFrom').length) loadTransferSummary();
        });
    </script>
@endpush
