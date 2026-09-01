@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Master Item Oil</h2>
                    <p class="text-muted mb-0">
                        @if ($isImc)
                            Stok yang dikelola oleh IMC.
                        @else
                            Stok milik department Anda — yang masih dititipkan di gudang IMC
                            maupun yang sudah ada di gudang sendiri.
                        @endif
                    </p>
                </div>
                <div class="col-auto">
                    @can('items.create')
                        <a href="{{ route('items.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Tambah Item
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <table class="table" id="dataTableItem" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode Item</th>
                                        <th>Nama Item</th>
                                        <th>Satuan</th>
                                        @if ($isImc)
                                            <th class="text-right">Stok di IMC</th>
                                        @else
                                            <th class="text-right">Stok di IMC</th>
                                            <th class="text-right">Stok di Gudang</th>
                                            <th class="text-right">Total</th>
                                        @endif
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
            const isImc = @json($isImc);

            // Staff melihat stoknya dipisah antara yang masih di IMC
            // dan yang sudah di gudang sendiri — supaya tahu kapan
            // perlu membuat transfer request.
            const stockColumns = isImc ? [{
                data: 'imc_stock',
                name: 'imc_stock',
                orderable: false,
                searchable: false,
                className: 'text-right'
            }] : [{
                    data: 'imc_stock',
                    name: 'imc_stock',
                    orderable: false,
                    searchable: false,
                    className: 'text-right'
                },
                {
                    data: 'local_stock',
                    name: 'local_stock',
                    orderable: false,
                    searchable: false,
                    className: 'text-right'
                },
                {
                    data: 'total_stock',
                    name: 'total_stock',
                    orderable: false,
                    searchable: false,
                    className: 'text-right'
                },
            ];

            $('#dataTableItem').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('items.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'item_no',
                        name: 'item_no'
                    },
                    {
                        data: 'item_desc',
                        name: 'item_desc'
                    },
                    {
                        data: 'item_uom',
                        name: 'item_uom'
                    },
                    ...stockColumns,
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });
        });

        // Konfirmasi hapus
        $(document).on('click', '.btn-delete', function() {
            const url = $(this).data('url');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Hapus data?',
                html: 'Yakin ingin menghapus <strong>' + name + '</strong>?',
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
