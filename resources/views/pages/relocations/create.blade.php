@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Pindah Lokasi Barang</h2>
                    <p class="text-muted mb-0">
                        Memindahkan barang antar gudang atau rak IMC. Kepemilikan stok
                        tidak berubah, dan tidak memengaruhi kartu stok.
                    </p>
                </div>
                <div class="col-auto">
                    <a href="{{ route('relocations.index') }}" class="btn btn-light btn-sm">
                        <span class="fe fe-list fe-16 mr-2"></span>Riwayat
                    </a>
                </div>
            </div>

            @if (session('error'))
                <div class="alert alert-danger">{{ session('error') }}</div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('relocations.store') }}" method="POST" id="relocationForm">
                        @csrf

                        <div class="form-group">
                            <label>Lot yang Dipindahkan <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="item_location_id" name="item_location_id" required>
                                <option value="">-- Pilih Lot --</option>
                                @foreach ($lots as $lot)
                                    @php
                                        $per = (float) $lot->qty_perpackage;
                                        $availablePkg = $per > 0 ? floor((float) $lot->qty_weight / $per) : 0;
                                    @endphp
                                    <option value="{{ $lot->id }}" data-warehouse="{{ $lot->warehouse_id }}"
                                        data-perpackage="{{ $per }}" data-available="{{ (int) $availablePkg }}"
                                        data-package="{{ $lot->package }}"
                                        {{ old('item_location_id') == $lot->id ? 'selected' : '' }}>
                                        {{ $lot->item->item_no }} —
                                        {{ $lot->receiving_lot ?? ($lot->vendor_lot ?? 'Lot #' . $lot->id) }}
                                        · {{ $lot->warehouse->name }} {{ $lot->warehouse->tag }}
                                        · {{ (int) $availablePkg }} pkg @ {{ number_format($per, 2, ',', '.') }} kg
                                        · {{ $lot->demander->code ?? '-' }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="lotHint"></small>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Gudang Tujuan <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="to_warehouse_id" name="to_warehouse_id" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('to_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} - {{ $wh->tag }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="form-text text-muted" id="warehouseHint"></small>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Jumlah Package <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" class="form-control" id="package_moved"
                                    name="package_moved" value="{{ old('package_moved') }}" disabled required>
                                <small class="form-text text-muted" id="maxHint"></small>
                            </div>

                            <div class="form-group col-md-3">
                                <label>Setara Berat</label>
                                <input type="text" class="form-control bg-light font-weight-bold" id="totalWeight"
                                    readonly placeholder="0,00 KG">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alasan Pemindahan</label>
                            <textarea class="form-control" name="reason" rows="2" placeholder="Contoh: Rak A direnovasi">{{ old('reason') }}</textarea>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                                <span class="fe fe-move fe-16 mr-2"></span>Pindahkan
                            </button>
                            <a href="{{ route('relocations.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function formatNumber(n) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(n);
        }

        function selectedLot() {
            const $opt = $('#item_location_id option:selected');

            if (!$opt.val()) return null;

            return {
                warehouseId: parseInt($opt.data('warehouse')),
                perPackage: parseFloat($opt.data('perpackage')),
                available: parseInt($opt.data('available')),
                package: $opt.data('package'),
            };
        }

        function validateForm() {
            const lot = selectedLot();
            const toWh = parseInt($('#to_warehouse_id').val()) || 0;
            const pkg = parseInt($('#package_moved').val()) || 0;

            if (!lot) {
                $('#btnSubmit').prop('disabled', true);
                return;
            }

            // Gudang tujuan tidak boleh sama dengan asal — kalau sama,
            // tidak ada yang berpindah.
            const sameWarehouse = toWh === lot.warehouseId;
            $('#warehouseHint').html(sameWarehouse ?
                '<span class="text-danger">Gudang tujuan sama dengan gudang asal.</span>' :
                '');

            const tooMany = pkg > lot.available;
            $('#package_moved').toggleClass('is-invalid', tooMany);

            $('#totalWeight').val(pkg > 0 ?
                formatNumber(pkg * lot.perPackage) + ' KG' :
                '');

            $('#btnSubmit').prop('disabled', !(toWh && !sameWarehouse && pkg > 0 && !tooMany));
        }

        $('#item_location_id').on('change', function() {
            const lot = selectedLot();

            if (!lot) {
                $('#package_moved').val('').prop('disabled', true);
                $('#lotHint, #maxHint').text('');
                $('#totalWeight').val('');
                $('#btnSubmit').prop('disabled', true);
                return;
            }

            $('#lotHint').text(
                'Kemasan ' + (lot.package ?? '-') + ' @ ' + formatNumber(lot.perPackage) +
                ' kg · tersedia ' + lot.available + ' package utuh.'
            );

            $('#package_moved')
                .prop('disabled', false)
                .attr('max', lot.available)
                .val('')
                .removeClass('is-invalid');

            $('#maxHint').text('Maksimal ' + lot.available + ' package.');
            $('#totalWeight').val('');

            validateForm();
        });

        $('#to_warehouse_id, #package_moved').on('change input', validateForm);
    </script>
@endpush
