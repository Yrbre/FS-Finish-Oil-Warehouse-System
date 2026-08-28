@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Stok Gudang (Item Onhand)</h2>
                </div>
            </div>

            <div class="row my-4">
                <div class="col-md-12">
                    <div class="card shadow">
                        <div class="card-body">

                            <div class="form-row mb-3">
                                <div class="col-md-4">
                                    <label class="small text-muted">Filter Item</label>
                                    <select id="filterItem" class="form-control select2">
                                        <option value="">Semua Item</option>
                                        @foreach ($items as $item)
                                            <option value="{{ $item->id }}">
                                                {{ $item->item_no }} - {{ $item->item_desc }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>

                                @if ($isImc)
                                    <div class="col-md-4">
                                        <label class="small text-muted">Filter Gudang</label>
                                        <select id="filterWarehouse" class="form-control select2">
                                            <option value="">Semua Gudang</option>
                                            @foreach ($warehouses as $wh)
                                                <option value="{{ $wh->id }}">{{ $wh->name }} -
                                                    {{ $wh->tag }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-4">
                                        <label class="small text-muted">Filter Pemilik</label>
                                        <select id="filterDemander" class="form-control select2">
                                            <option value="">Semua Department</option>
                                            @foreach ($departments as $dept)
                                                <option value="{{ $dept->id }}">{{ $dept->code }} -
                                                    {{ $dept->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif
                            </div>

                            <table class="table" id="dataTableItemLocation" style="width:100%">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Item</th>
                                        <th>Gudang</th>
                                        <th>Pemilik</th>
                                        <th>Receiving Lot</th>
                                        <th>Vendor Lot</th>
                                        <th>Exp Date</th>
                                        <th>Package</th>
                                        <th>Berat</th>
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
    <div class="modal fade" id="disposeModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form method="POST" id="disposeForm">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buang Lot</h5>
                        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-2">
                            <strong id="disposeName"></strong><br>
                            <span class="text-muted">Stok: <span id="disposeQty"></span> kg</span>
                        </p>
                        <div class="alert alert-warning small">
                            Seluruh stok lot ini akan dinolkan dan dikeluarkan dari perhitungan FEFO.
                            Datanya tetap tersimpan untuk audit. Tindakan ini tidak dapat dibatalkan.
                        </div>
                        <div class="form-group mb-0">
                            <label>Alasan Pembuangan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="disposal_reason" rows="3" required minlength="10"
                                placeholder="Contoh: Kemasan bocor, isi tercemar air"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-danger">Buang Lot</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        $(function() {
            const table = $('#dataTableItemLocation').DataTable({
                processing: true,
                serverSide: true,
                responsive: true,
                ajax: {
                    url: '{{ route('item-locations.index') }}',
                    data: function(d) {
                        d.item_id = $('#filterItem').val();
                        d.warehouse_id = $('#filterWarehouse').val();
                        d.demander_id = $('#filterDemander').val();
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
                        data: 'warehouse',
                        name: 'warehouse',
                        orderable: false
                    },
                    {
                        data: 'demander',
                        name: 'demander',
                        orderable: false
                    },
                    {
                        data: 'receiving_lot',
                        name: 'receiving_lot'
                    },
                    {
                        data: 'vendor_lot',
                        name: 'vendor_lot'
                    },
                    {
                        data: 'exp_date',
                        name: 'exp_date'
                    },
                    {
                        data: 'package',
                        name: 'package',
                        orderable: false,
                        searchable: false
                    },
                    {
                        data: 'qty_weight',
                        name: 'qty_weight'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false
                    },
                ],
            });

            $('#filterItem, #filterWarehouse, #filterDemander').on('change', function() {
                table.ajax.reload();
            });
        });

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

        $(document).on('click', '.btn-dispose', function() {
            const id = $(this).data('id');

            $('#disposeName').text($(this).data('name'));
            $('#disposeQty').text($(this).data('qty'));
            $('#disposeForm')
                .attr('action', `/item-locations/${id}/dispose`)
                .find('textarea').val('');

            $('#disposeModal').modal('show');
        });
    </script>
@endpush
