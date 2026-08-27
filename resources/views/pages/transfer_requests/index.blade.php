@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Permintaan Kirim Barang</h2>
                </div>
                @if (auth()->user()->canIssueReceipt())
                    <div class="col-auto">
                        <button type="button" id="btn-cetak-terpilih" class="btn btn-warning" disabled>
                            <span class="fe fe-printer fe-16 mr-2"></span>
                            Cetak TTB Terpilih (<span id="jumlah-terpilih">0</span>)
                        </button>
                    </div>
                @endif
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
                                        <option value="approved">Approved</option>
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

            {{-- Modal cetak batch — letter_date wajib karena tanggal ini
         yang tercetak di dokumen dan boleh di-backdate. --}}
            <div class="modal fade" id="cetakBatchModal" tabindex="-1" role="dialog">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Cetak Tanda Terima Barang</h5>
                            <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                        </div>
                        <div class="modal-body">
                            <p>
                                <strong id="modal-jumlah">0</strong> permintaan akan dicetak.
                            </p>
                            <div class="alert alert-info small mb-3">
                                Permintaan yang belum punya tanda terima akan diterbitkan nomornya
                                dan statusnya berubah menjadi <strong>dalam perjalanan</strong>.
                                Semua permintaan harus sudah berstatus <strong>approved</strong>.
                            </div>
                            <div class="form-group mb-0">
                                <label>Tanggal Kirim <span class="text-danger">*</span></label>
                                <input type="date" id="modal-letter-date" class="form-control"
                                    value="{{ now()->toDateString() }}" required>
                                <small class="form-text text-muted">Tanggal yang tercetak di dokumen.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                            <button type="button" class="btn btn-warning" id="btn-konfirmasi-cetak">
                                <span class="fe fe-printer fe-16 mr-2"></span>Cetak
                            </button>
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
                        // Nama item pertama + jumlah item lainnya
                        data: 'item_summary',
                        name: 'item_summary',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'destination',
                        name: 'destination',
                        orderable: false
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

            // Reset centang tiap kali data di-reload (ganti halaman,
            // filter, atau setelah cetak).
            table.on('draw', function() {
                $('#check-all').prop('checked', false);

                // Kembalikan centang baris yang masih terpilih —
                // supaya pilihan lintas halaman tidak hilang.
                $('#dataTableTransferRequest .row-checkbox').each(function() {
                    $(this).prop('checked', selectedIds.has($(this).val()));
                });

                updateTombolCetak();
            });

            // klik tombol cetak
            // Buka modal untuk mengisi tanggal kirim
            $('#btn-cetak-terpilih').on('click', function() {
                if (selectedIds.size === 0) return;

                $('#modal-jumlah').text(selectedIds.size);
                $('#cetakBatchModal').modal('show');
            });

            $('#btn-konfirmasi-cetak').on('click', function() {
                const letterDate = $('#modal-letter-date').val();

                if (!letterDate) {
                    $('#modal-letter-date').addClass('is-invalid').focus();
                    return;
                }

                const container = $('#hidden-ids-container');
                container.empty();

                // Tanggal ikut dibuat di sini, bukan mengandalkan hidden
                // input terpisah — modal bisa dipindah Bootstrap ke luar
                // form dan nilainya jadi tidak terkirim.
                container.append(
                    `<input type="hidden" name="letter_date" value="${letterDate}">`
                );

                selectedIds.forEach(function(id) {
                    container.append(`<input type="hidden" name="ids[]" value="${id}">`);
                });

                $('#form-cetak-batch').submit();
                $('#cetakBatchModal').modal('hide');

                Swal.fire({
                    title: 'Memproses...',
                    text: 'Tanda terima dibuka di tab baru.',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading(),
                });

                setTimeout(function() {
                    selectedIds.clear();
                    updateTombolCetak();
                    Swal.close();
                    table.ajax.reload(null, false);
                }, 1800);
            });

            $('#modal-letter-date').on('input', function() {
                $(this).removeClass('is-invalid');
            });
        });
    </script>
@endpush
