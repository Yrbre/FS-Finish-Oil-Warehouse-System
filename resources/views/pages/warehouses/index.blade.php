@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Master Gudang</h2>
                </div>
                <div class="col-auto">
                    @can('warehouses.create')
                        <a href="{{ route('warehouses.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Tambah Gudang
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">

                            <div class="form-row mb-3">
                                <div class="col-md-4">
                                    <label for="filterDepartment" class="small text-muted">Filter Department</label>
                                    <select id="filterDepartment" class="form-control">
                                        <option value="">Semua Department</option>
                                        @foreach ($departments as $dept)
                                            <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <table class="table" id="dataTableWarehouse" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama Gudang</th>
                                        <th>TAG</th>
                                        <th>Department</th>
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
            const table = $('#dataTableWarehouse').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('warehouses.index') }}',
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
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'tag',
                        name: 'tag'
                    },
                    {
                        data: 'department',
                        name: 'department',
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

            // Reload tabel saat filter department berubah
            $('#filterDepartment').on('change', function() {
                table.ajax.reload();
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
