@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Laporan</h2>
                </div>
            </div>

            {{-- Ringkasan stok per gudang --}}
            <div class="row my-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <strong class="card-title">Ringkasan Stok per Gudang</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Gudang</th>
                                            <th>Jumlah Jenis Item</th>
                                            <th class="text-right">Total Stok (KG)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($stockByWarehouse as $row)
                                            <tr>
                                                <td>{{ $row->warehouse_name }}</td>
                                                <td>{{ $row->item_count }}</td>
                                                <td class="text-right">
                                                    {{ number_format((float) $row->total_stock, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted py-3">Belum ada data stok.
                                                </td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                    @if ($stockByWarehouse->isNotEmpty())
                                        <tfoot>
                                            <tr class="font-weight-bold">
                                                <td colspan="2" class="text-right">Total Keseluruhan</td>
                                                <td class="text-right">
                                                    {{ number_format($stockByWarehouse->sum('total_stock'), 2, ',', '.') }}
                                                </td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Near expiry lengkap --}}
            <div class="row my-4">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header">
                            <strong class="card-title">Stok Mendekati Expired (30 hari ke depan)</strong>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-striped mb-0">
                                    <thead>
                                        <tr>
                                            <th>Item</th>
                                            <th>Gudang</th>
                                            <th>Vendor Lot</th>
                                            <th>Exp Date</th>
                                            <th>Sisa Hari</th>
                                            <th class="text-right">Stok</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($nearExpiry as $lot)
                                            @php
                                                $daysLeft = now()->startOfDay()->diffInDays($lot->exp_date, false);
                                            @endphp
                                            <tr
                                                class="{{ $daysLeft < 0 ? 'table-danger' : ($daysLeft <= 7 ? 'table-warning' : '') }}">
                                                <td>{{ $lot->item->item_desc }}</td>
                                                <td>{{ $lot->warehouse->name }}</td>
                                                <td>{{ $lot->vendor_lot ?? '-' }}</td>
                                                <td>{{ $lot->exp_date->format('d-m-Y') }}</td>
                                                <td>
                                                    @if ($daysLeft < 0)
                                                        <span class="badge badge-danger">Sudah expired {{ abs($daysLeft) }}
                                                            hari</span>
                                                    @else
                                                        <span
                                                            class="badge {{ $daysLeft <= 7 ? 'badge-warning' : 'badge-light' }}">{{ $daysLeft }}
                                                            hari lagi</span>
                                                    @endif
                                                </td>
                                                <td class="text-right">
                                                    {{ number_format((float) $lot->qty_weight, 2, ',', '.') }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="6" class="text-center text-muted py-3">Tidak ada stok yang
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
    </div>
@endsection
