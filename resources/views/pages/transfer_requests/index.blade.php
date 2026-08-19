@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Permintaan Kirim Barang</h2>
                </div>
                <div class="col-auto">
                    <button type="button" id="btn-cetak-terpilih" class="btn btn-warning" disabled>
                        <span class="fe fe-printer fe-16 mr-2"></span>
                        Cetak TTB Terpilih (<span id="jumlah-terpilih">0</span>)
                    </button>
                </div>
                @can('transfer-requests.create')
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
                                        <th><input type="checkbox" id="check-all"></th>
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
                            <form id="form-cetak-batch" action="{{ route('transfer-requests.cetak-batch') }}" method="POST"
                                target="_blank">
                                @csrf
                                <div id="hidden-ids-container"></div>
                            </form>
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
                    [1, 'desc']
                ],
                ajax: {
                    url: '{{ route('transfer-requests.index') }}',
                    data: function(d) {
                        d.status = $('#filterStatus').val();
                    },
                },
                columns: [{
                        data: 'checkbox',
                        name: 'checkbox',
                        orderable: false,
                        searchable: false
                    },
                    {
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

            // ==== Bagian pilih & cetak terpilih ====
            let selectedIds = new Set();

            function updateTombolCetak() {
                $('#jumlah-terpilih').text(selectedIds.size);
                $('#btn-cetak-terpilih').prop('disabled', selectedIds.size === 0);
            }

            // checkbox per baris (delegasi ke #dataTableTransferRequad, bukan id yang salah)
            $('#dataTableTransferRequest').on('change', '.row-checkbox', function() {
                let id = $(this).val();
                if ($(this).is(':checked')) {
                    selectedIds.add(id);
                } else {
                    selectedIds.delete(id);
                }
                updateTombolCetak();
            });

            // checkbox pilih semua (hanya baris yang sedang tampil di halaman ini)
            $('#check-all').on('change', function() {
                let checked = $(this).is(':checked');
                $('#dataTableTransferRequest .row-checkbox').prop('checked', checked).trigger('change');
            });

            // reset centang tiap kali data di-reload (ganti halaman/filter)
            // biar checkbox "select all" ikut ke-uncheck otomatis
            table.on('draw', function() {
                $('#check-all').prop('checked', false);
            });

            // klik tombol cetak
            $('#btn-cetak-terpilih').on('click', function() {
                if (selectedIds.size === 0) return;

                let container = $('#hidden-ids-container');
                container.empty();

                selectedIds.forEach(function(id) {
                    container.append(`<input type="hidden" name="ids[]" value="${id}">`);
                });

                $('#form-cetak-batch').submit();
            });
        });
    </script>
@endpush
