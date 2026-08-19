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

            {{-- Widget ringkasan --}}
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
                            <p class="small text-muted mb-1 text-uppercase">Total Stok (KG)</p>
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
                {{-- Transaksi terbaru --}}
                <div class="col-md-12 col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header">
                            <strong class="card-title">Transaksi Terbaru</strong>
                            @canany(['transactions.porc.view', 'transactions.cons.view', 'transactions.adj.view'])
                                <a href="{{ route('transactions.index') }}" class="float-right small text-muted">Lihat semua</a>
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
                                            <td colspan="4" class="text-center text-muted py-3">Belum ada transaksi.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                {{-- Near expiry --}}
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
                                        <th>Exp Date</th>
                                        <th class="text-right">Stok</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($nearExpiry as $lot)
                                        @php
                                            $daysLeft = now()->diffInDays($lot->exp_date, false);
                                        @endphp
                                        <tr
                                            class="{{ $daysLeft < 0 ? 'table-danger' : ($daysLeft <= 7 ? 'table-warning' : '') }}">
                                            <td class="text-dark">{{ $lot->item->item_desc }}</td>
                                            <td class="text-dark">{{ $lot->warehouse->name }}</td>
                                            <td class="text-dark">{{ $lot->exp_date->format('d-m-Y') }}</td>
                                            <td class="text-right text-dark">
                                                {{ number_format((float) $lot->qty_weight, 2, ',', '.') }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-3">Tidak ada stok yang
                                                mendekati expired.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection
