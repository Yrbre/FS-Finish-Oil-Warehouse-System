@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Laporan</h2>
                </div>
            </div>

            {{-- Control LOT CAT FIN --}}
            <div class="col-md-12 mb-4">
                <div class="card shadow">
                    <div class="card-body">
                        <ul class="nav nav-pills nav-fill mb-3" id="pills-tab" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="pills-FY-tab" data-toggle="pill" href="#pills-FY"
                                    role="tab" aria-controls="pills-FY" aria-selected="true">FY</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-SF-tab" data-toggle="pill" href="#pills-SF" role="tab"
                                    aria-controls="pills-SF" aria-selected="false">SF</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-HSF-tab" data-toggle="pill" href="#pills-HSF" role="tab"
                                    aria-controls="pills-HSF" aria-selected="false">HSF</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="pills-Rekap-tab" data-toggle="pill" href="#pills-Rekap"
                                    role="tab" aria-controls="pills-Rekap" aria-selected="false">Rekap</a>
                            </li>
                        </ul>
                        <div class="tab-content mb-1" id="pills-tabContent">
                            <div class="tab-pane fade show active" id="pills-FY" role="tabpanel"
                                aria-labelledby="pills-FY-tab">
                                <div class="row align-items-center mb-2">
                                    <div class="col">
                                        <h2 class="h5 page-title">Laporan FY (BULAN)</h2>
                                    </div>
                                </div>
                                <table class="table" id="dataTableItem" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Item</th>
                                            <th>Expired(Bulan)</th>
                                            <th>Berat(KG)</th>
                                            <th>NO PO</th>
                                            <th>Vendor Lot</th>
                                            <th>Tanggal Produksi</th>
                                            <th>Expired Date</th>
                                            <th>Month Overdue</th>
                                            <th>Jumlah</th>
                                            <th>Tanggal Penerimaan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($itemFY as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->item->item_desc ?? '-' }}</td>
                                                <td>
                                                    12 Bulan
                                                </td>
                                                <td>{{ number_format($item->qty_weight, 2, ',', '.') }}</td>
                                                <td>{{ $item->po_number }}</td>
                                                <td>{{ $item->vendor_lot }}</td>
                                                <td>{{ $item->production_date ? \Carbon\Carbon::parse($item->production_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td>{{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td> <span class="badge badge-{{ $item->exp_info['class'] }}"
                                                        style="font-size: 12px;">
                                                        {{ $item->exp_info['text'] }}
                                                    </span></td>
                                                <td>{{ $item->qty_package ?? '-' }}</td>
                                                <td>{{ $item->received_date ? \Carbon\Carbon::parse($item->received_date)->format('d-M-Y') : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center text-muted">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="pills-SF" role="tabpanel" aria-labelledby="pills-SF-tab">
                                <div class="row align-items-center mb-2">
                                    <div class="col">
                                        <h2 class="h5 page-title">Laporan SF (BULAN)</h2>
                                    </div>
                                </div>
                                <table class="table" id="dataTableItem" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Item</th>
                                            <th>Expired(Bulan)</th>
                                            <th>Berat(KG)</th>
                                            <th>NO PO</th>
                                            <th>Vendor Lot</th>
                                            <th>Tanggal Produksi</th>
                                            <th>Expired Date</th>
                                            <th>Month Overdue</th>
                                            <th>Jumlah</th>
                                            <th>Tanggal Penerimaan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($itemSF as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->item->item_desc ?? '-' }}</td>
                                                <td>
                                                    12 Bulan
                                                </td>
                                                <td>{{ number_format($item->qty_weight, 2, ',', '.') }}</td>
                                                <td>{{ $item->po_number }}</td>
                                                <td>{{ $item->vendor_lot }}</td>
                                                <td>{{ $item->production_date ? \Carbon\Carbon::parse($item->production_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td>{{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td> <span class="badge badge-{{ $item->exp_info['class'] }}"
                                                        style="font-size: 12px;">
                                                        {{ $item->exp_info['text'] }}
                                                    </span></td>
                                                <td>{{ $item->qty_package ?? '-' }}</td>
                                                <td>{{ $item->received_date ? \Carbon\Carbon::parse($item->received_date)->format('d-M-Y') : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center text-muted">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="pills-HSF" role="tabpanel" aria-labelledby="pills-HSF-tab">
                                <div class="row align-items-center mb-2">
                                    <div class="col">
                                        <h2 class="h5 page-title">Laporan HSF (BULAN)</h2>
                                    </div>
                                </div>
                                <table class="table" id="dataTableItem" style="width:100%">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Nama Item</th>
                                            <th>Expired(Bulan)</th>
                                            <th>Berat(KG)</th>
                                            <th>NO PO</th>
                                            <th>Vendor Lot</th>
                                            <th>Tanggal Produksi</th>
                                            <th>Expired Date</th>
                                            <th>Month Overdue</th>
                                            <th>Jumlah</th>
                                            <th>Tanggal Penerimaan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($itemHSF as $item)
                                            <tr>
                                                <td>{{ $loop->iteration }}</td>
                                                <td>{{ $item->item->item_desc ?? '-' }}</td>
                                                <td>
                                                    12 Bulan
                                                </td>
                                                <td>{{ number_format($item->qty_weight, 2, ',', '.') }}</td>
                                                <td>{{ $item->po_number }}</td>
                                                <td>{{ $item->vendor_lot }}</td>
                                                <td>{{ $item->production_date ? \Carbon\Carbon::parse($item->production_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td>{{ $item->exp_date ? \Carbon\Carbon::parse($item->exp_date)->format('M-Y') : '-' }}
                                                </td>
                                                <td> <span class="badge badge-{{ $item->exp_info['class'] }}"
                                                        style="font-size: 12px;">
                                                        {{ $item->exp_info['text'] }}
                                                    </span></td>
                                                <td>{{ $item->qty_package ?? '-' }}</td>
                                                <td>{{ $item->received_date ? \Carbon\Carbon::parse($item->received_date)->format('d-M-Y') : '-' }}
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="12" class="text-center text-muted">Tidak ada data.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                            <div class="tab-pane fade" id="pills-Rekap" role="tabpanel" aria-labelledby="pills-Rekap-tab">
                                INI AKAN JADI DATA REKAP </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
