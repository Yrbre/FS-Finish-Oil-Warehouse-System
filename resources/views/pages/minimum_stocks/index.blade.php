@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Minimum Stock per Department</h2>
                    <p class="text-muted mb-0">
                        Ambang peringatan stok, dihitung dari stok di gudang sendiri
                        ditambah yang masih dititipkan di IMC. Pengaturan di sini
                        menimpa nilai default pada master item.
                    </p>
                </div>
                @can('minimum-stocks.create')
                    <div class="col-auto">
                        <a href="{{ route('minimum-stocks.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Tambah Pengaturan
                        </a>
                    </div>
                @endcan
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">

                            <div class="form-row mb-3">
                                <div class="col-md-4">
                                    <label class="small text-muted">Filter Department</label>
                                    <select id="filterDepartment" class="form-control select2">
                                        <option value="">Semua Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}">
                                                {{ $dept->code }} - {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <table class="table" id="dataTableMinStock" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Item</th>
                                        <th>Department</th>
                                        <th class="text-right">Minimum Stock</th>
                                        <th>Status</th>
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
            const table = $('#dataTableMinStock').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('minimum-stocks.index') }}',
                    data: function(d) {
                        d.department_id = $('#filterDepartment').val();
                    },
                },
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
                        data: 'department',
                        name: 'department',
                        orderable: false
                    },
                    {
                        data: 'min_stock',
                        name: 'min_stock',
                        className: 'text-right'
                    },
                    {
                        data: 'status',
                        name: 'status',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            $('#filterDepartment').on('change', function() {
                table.ajax.reload();
            });
        });

        $(document).on('click', '.btn-delete', function() {
            const url = $(this).data('url');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Hapus pengaturan?',
                html: 'Yakin ingin menghapus <strong>' + name + '</strong>?<br>' +
                    '<small class="text-muted">Department akan kembali memakai nilai default pada master item.</small>',
                icon: 'warning',
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
