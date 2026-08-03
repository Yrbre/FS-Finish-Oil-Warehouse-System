@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Manajemen User</h2>
                </div>
                <div class="col-auto">
                    @can('users.create')
                        <a href="{{ route('users.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Tambah User
                        </a>
                    @endcan
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">
                            <table class="table" id="dataTableUser" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Email</th>
                                        <th>Department</th>
                                        <th>Role</th>
                                        <th>Approver Transfer</th>
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
            $('#dataTableUser').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: '{{ route('users.index') }}',
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
                        data: 'email',
                        name: 'email'
                    },
                    {
                        data: 'department',
                        name: 'department',
                        orderable: false
                    },
                    {
                        data: 'role',
                        name: 'role',
                        orderable: false
                    },
                    {
                        data: 'is_approver',
                        name: 'is_approver',
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
        });

        $(document).on('click', '.btn-delete', function() {
            const url = $(this).data('url');
            const name = $(this).data('name');

            Swal.fire({
                title: 'Hapus user?',
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
