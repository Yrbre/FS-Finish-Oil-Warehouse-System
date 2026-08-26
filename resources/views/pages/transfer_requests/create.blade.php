@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Buat Permintaan Kirim Barang</h2>
                    <p class="text-muted mb-0">
                        Pilih ukuran kemasan dan jumlah package yang dibutuhkan.
                        Gudang asal ditentukan sistem otomatis (FEFO).
                    </p>
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
                    <form action="{{ route('transfer-requests.store') }}" method="POST" id="trForm">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2" id="item_id" name="item_id" required>
                                    <option value="">-- Pilih Item --</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}"
                                            {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->item_no }} - {{ $item->item_desc }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Gudang Tujuan <span class="text-danger">*</span></label>
                                <select class="form-control select2" name="destination_warehouse_id" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('destination_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} - {{ $wh->tag }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        {{-- Daftar ukuran kemasan yang benar-benar tersedia di
                             gudang IMC untuk department ini. Diisi lewat AJAX
                             supaya staff tidak bisa meminta ukuran yang tidak
                             ada atau melebihi stok. --}}
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Ukuran Kemasan <span class="text-danger">*</span></label>
                                <select class="form-control" id="requested_perpackage" name="requested_perpackage" disabled
                                    required>
                                    <option value="">-- Pilih item terlebih dahulu --</option>
                                </select>
                                <small class="form-text text-muted" id="sizeHint"></small>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Jumlah Package <span class="text-danger">*</span></label>
                                <input type="number" step="1" min="1" class="form-control"
                                    id="requested_package" name="requested_package" value="{{ old('requested_package') }}"
                                    disabled required>
                                <small class="form-text text-muted" id="maxPackageHint"></small>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Setara Berat</label>
                                <input type="text" class="form-control bg-light font-weight-bold" id="totalWeight"
                                    readonly placeholder="0,00 KG">
                            </div>

                            <div class="form-group col-md-6">
                                <label>Tanggal Kebutuhan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="expected_date"
                                    value="{{ old('expected_date', now()->addDays(3)->toDateString()) }}" required>
                            </div>
                        </div>


                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary" id="btnSubmit" disabled>
                                <span class="fe fe-save fe-16 mr-2"></span>Ajukan Permintaan
                            </button>
                            <a href="{{ route('transfer-requests.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Cache data ukuran dari AJAX, dipakai saat select berubah.
        let sizeData = {};

        function formatNumber(n) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(n);
        }

        function resetPackageInput() {
            $('#requested_package').val('').prop('disabled', true)
                .removeAttr('max').removeClass('is-invalid');
            $('#maxPackageHint').text('');
            $('#sizeHint').text('');
            $('#totalWeight').val('');
            $('#btnSubmit').prop('disabled', true);
        }

        function loadPackageSizes() {
            const itemId = $('#item_id').val();

            sizeData = {};
            resetPackageInput();
            $('#requested_perpackage')
                .html('<option value="">-- Memuat... --</option>')
                .prop('disabled', true);

            if (!itemId) {
                $('#requested_perpackage')
                    .html('<option value="">-- Pilih item terlebih dahulu --</option>');
                return;
            }

            $.get('{{ route('transfer-requests.package-sizes') }}', {
                item_id: itemId
            }).done(function(sizes) {
                if (!sizes.length) {
                    $('#requested_perpackage')
                        .html('<option value="">-- Stok tidak tersedia --</option>');
                    $('#sizeHint').html(
                        '<span class="text-danger">Tidak ada stok item ini milik department Anda di gudang IMC.</span>'
                    );
                    return;
                }

                let options = '<option value="">-- Pilih Ukuran --</option>';

                sizes.forEach(function(s) {
                    sizeData[s.qty_perpackage] = s;

                    const label = (s.package ?? 'Kemasan') + ' ' +
                        formatNumber(s.qty_perpackage) + ' kg — tersedia ' +
                        s.available_package + ' package';

                    options += `<option value="${s.qty_perpackage}">${label}</option>`;
                });

                $('#requested_perpackage').html(options).prop('disabled', false);
                $('#sizeHint').text(sizes.length + ' ukuran kemasan tersedia.');
            }).fail(function() {
                $('#requested_perpackage')
                    .html('<option value="">-- Gagal memuat --</option>');
                $('#sizeHint').html('<span class="text-danger">Gagal memuat data stok.</span>');
            });
        }

        function selectedSize() {
            return sizeData[$('#requested_perpackage').val()] ?? null;
        }

        function updateTotalWeight() {
            const size = selectedSize();
            if (!size) return;

            const pkg = parseFloat($('#requested_package').val()) || 0;
            const total = pkg * parseFloat(size.qty_perpackage);

            $('#totalWeight').val(total > 0 ? formatNumber(total) + ' KG' : '');

            // Tidak boleh melebihi package utuh yang tersedia.
            const melebihi = pkg > size.available_package;
            $('#requested_package').toggleClass('is-invalid', melebihi);
            $('#btnSubmit').prop('disabled', !(pkg > 0 && !melebihi));
        }

        $('#item_id').on('change', loadPackageSizes);

        $('#requested_perpackage').on('change', function() {
            const size = selectedSize();

            if (!size) {
                resetPackageInput();
                return;
            }

            const exp = size.nearest_exp ?
                new Date(size.nearest_exp).toLocaleDateString('id-ID', {
                    month: 'short',
                    year: 'numeric'
                }) :
                '-';

            $('#sizeHint').html(
                'Total ' + formatNumber(size.total_weight) + ' kg dari ' +
                size.lot_count + ' lot &middot; exp terdekat ' + exp
            );

            $('#requested_package')
                .prop('disabled', false)
                .attr('max', size.available_package)
                .val('')
                .removeClass('is-invalid')
                .focus();

            $('#maxPackageHint').text('Maksimal ' + size.available_package + ' package.');
            $('#totalWeight').val('');
            $('#btnSubmit').prop('disabled', true);
        });

        $('#requested_package').on('input change', updateTotalWeight);
    </script>
@endpush
