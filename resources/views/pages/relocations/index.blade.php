@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Riwayat Pindah Lokasi</h2>
                    <p class="text-muted mb-0">
                        Pemindahan barang antar gudang IMC. Tidak memengaruhi kartu stok
                        karena jumlah stok tidak berubah — hanya lokasinya.
                    </p>
                </div>
                @can('relocations.create')
                    <div class="col-auto">
                        <a href="{{ route('relocations.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Pindahkan Barang
                        </a>
                    </div>
                @endcan
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <table class="table" id="dataTableRelocation" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Item</th>
                                        <th>Dari</th>
                                        <th>Ke</th>
                                        <th>Pemilik</th>
                                        <th>Jumlah</th>
                                        <th>Waktu</th>
                                    </tr>
                                </thead>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            $('#dataTableRelocation').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('relocations.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'item',
                        name: 'item',
                        orderable: false
                    },
                    {
                        data: 'from',
                        name: 'from',
                        orderable: false
                    },
                    {
                        data: 'to',
                        name: 'to',
                        orderable: false
                    },
                    {
                        data: 'demander',
                        name: 'demander',
                        orderable: false
                    },
                    {
                        data: 'qty',
                        name: 'qty',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'moved',
                        name: 'moved',
                        orderable: false,
                        searchable: false
                    },
                ],
            });
        });
    </script>
@endpush
