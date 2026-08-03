@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Master Department</h2>
                </div>
                <div class="col-auto">
                    @can('departments.create')
                        <a href="{{ route('departments.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Tambah Department
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <table class="table datatables" id="dataTableDepartment" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Nama Department</th>
                                        <th>Jumlah Gudang</th>
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
            $('#dataTableDepartment').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('departments.index') }}',
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'code',
                        name: 'code'
                    },
                    {
                        data: 'name',
                        name: 'name'
                    },
                    {
                        data: 'warehouse_count',
                        name: 'warehouse_count',
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
        });

        // Konfirmasi hapus — submit form DELETE dinamis setelah dikonfirmasi
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
