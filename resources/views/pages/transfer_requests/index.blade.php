@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Transfer Request</h2>
                </div>
                @can('manage-transfer-request')
                    <div class="col-auto">
                        <a href="{{ route('transfer-requests.create') }}" class="btn btn-primary btn-sm">
                            <span class="fe fe-plus fe-16 mr-2"></span>Buat Request
                        </a>
                    </div>
                @endcan
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">

                            <div class="form-row mb-3">
                                <div class="col-md-3">
                                    <label class="small text-muted">Filter Status</label>
                                    <select id="filterStatus" class="form-control">
                                        <option value="">Semua Status</option>
                                        <option value="new">New</option>
                                        <option value="in_transit">In Transit</option>
                                        <option value="received">Received</option>
                                        <option value="rejected">Rejected</option>
                                        <option value="cancelled">Cancelled</option>
                                    </select>
                                </div>
                            </div>

                            <table class="table" id="dataTableTransferRequest" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Kode</th>
                                        <th>Item</th>
                                        <th>Tujuan</th>
                                        <th>Qty</th>
                                        <th>Tgl Dibutuhkan</th>
                                        <th>Requester</th>
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
            const table = $('#dataTableTransferRequest').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                order: [
                    [0, 'desc']
                ],
                ajax: {
                    url: '{{ route('transfer-requests.index') }}',
                    data: function(d) {
                        d.status = $('#filterStatus').val();
                    },
                },
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'transfer_code',
                        name: 'transfer_code'
                    },
                    {
                        data: 'item',
                        name: 'item',
                        orderable: false
                    },
                    {
                        data: 'destination',
                        name: 'destination',
                        orderable: false
                    },
                    {
                        data: 'requested_qty',
                        name: 'requested_qty'
                    },
                    {
                        data: 'expected_date',
                        name: 'expected_date'
                    },
                    {
                        data: 'requester',
                        name: 'requester',
                        orderable: false
                    },
                    {
                        data: 'status',
                        name: 'status'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            $('#filterStatus').on('change', function() {
                table.ajax.reload();
            });
        });
    </script>
@endpush
