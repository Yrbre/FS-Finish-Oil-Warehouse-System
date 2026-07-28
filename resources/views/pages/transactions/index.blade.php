@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Daftar Transaksi</h2>
                </div>
                <div class="col-auto">
                    <a href="{{ route('transactions.porc.create') }}" class="btn btn-success btn-sm">
                        <span class="fe fe-plus fe-16 mr-1"></span>Supply Oil
                    </a>
                    <a href="{{ route('transactions.cons.create') }}" class="btn btn-danger btn-sm">
                        <span class="fe fe-minus fe-16 mr-1"></span>Pemakaian
                    </a>
                    <a href="{{ route('transactions.adj.create') }}" class="btn btn-warning btn-sm">
                        <span class="fe fe-edit-2 fe-16 mr-1"></span>Adjustment
                    </a>
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">

                            <div class="form-row mb-3">
                                <div class="col-md-3">
                                    <label class="small text-muted">Jenis</label>
                                    <select id="filterDocType" class="form-control">
                                        <option value="">Semua Jenis</option>
                                        <option value="PORC">PORC (Masuk)</option>
                                        <option value="CONS">CONS (Keluar)</option>
                                        <option value="ADJ">ADJ (Koreksi)</option>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-muted">Gudang</label>
                                    <select id="filterWarehouse" class="form-control select2">
                                        <option value="">Semua Gudang</option>
                                        @foreach ($warehouses as $wh)
                                            <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-muted">Dari Tanggal</label>
                                    <input type="date" id="filterDateFrom" class="form-control">
                                </div>
                                <div class="col-md-3">
                                    <label class="small text-muted">Sampai Tanggal</label>
                                    <input type="date" id="filterDateTo" class="form-control">
                                </div>
                            </div>

                            <table class="table" id="dataTableTransaction" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Tanggal</th>
                                        <th>Jenis</th>
                                        <th>Item</th>
                                        <th>Gudang</th>
                                        <th>Masuk</th>
                                        <th>Keluar</th>
                                        <th>Oleh</th>
                                        <th>Aksi</th>
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
            const table = $('#dataTableTransaction').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                order: [
                    [1, 'desc']
                ],
                ajax: {
                    url: '{{ route('transactions.index') }}',
                    data: function(d) {
                        d.doc_type = $('#filterDocType').val();
                        d.warehouse_id = $('#filterWarehouse').val();
                        d.date_from = $('#filterDateFrom').val();
                        d.date_to = $('#filterDateTo').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'trans_date',
                        name: 'trans_date'
                    },
                    {
                        data: 'doc_type',
                        name: 'doc_type'
                    },
                    {
                        data: 'item',
                        name: 'item',
                        orderable: false
                    },
                    {
                        data: 'warehouse',
                        name: 'warehouse',
                        orderable: false
                    },
                    {
                        data: 'in_qty',
                        name: 'in_qty'
                    },
                    {
                        data: 'out_qty',
                        name: 'out_qty'
                    },
                    {
                        data: 'created_by',
                        name: 'created_by',
                        orderable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            $('#filterDocType, #filterWarehouse, #filterDateFrom, #filterDateTo').on('change', function() {
                table.ajax.reload();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const url = $(this).data('url');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Hapus transaksi?',
                html: 'Yakin ingin menghapus transaksi <strong>' + name +
                    '</strong>?<br><small class="text-muted">Stok akan dikembalikan.</small>',
                icon: 'warning',
                theme: 'dark',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#dc3545',
                reverseButtons: true,
            }).then((result) => {
                if (result.isConfirmed) {
                    const token = '{{ csrf_token() }}';
                    const form = $('<form>', {
                        method: 'POST',
                        action: url
                    }).append(
                        '<input type="hidden" name="_token" value="' + token + '">',
                        '<input type="hidden" name="_method" value="DELETE">'
                    );
                    form.appendTo('body').submit();
                }
            });
        });
    </script>
@endpush
