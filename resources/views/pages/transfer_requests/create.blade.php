@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-lg-10">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Buat Permintaan Kirim Barang</h2>
                    <p class="text-muted mb-0">
                        Satu permintaan bisa berisi beberapa item (maks. 10).
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

            <form action="{{ route('transfer-requests.store') }}" method="POST" id="trForm">
                @csrf

                <div class="card shadow mb-4">
                    <div class="card-body">
                        <div class="form-row">
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

                            <div class="form-group col-md-6">
                                <label>Tanggal Kebutuhan <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="expected_date"
                                    value="{{ old('expected_date', now()->addDays(3)->toDateString()) }}" required>
                            </div>
                        </div>

                        <div class="form-group mb-0">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>

                <div id="itemList"></div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" class="btn btn-outline-primary" id="btnAddItem">
                        <span class="fe fe-plus mr-1"></span>Tambah Item
                    </button>

                    <div>
                        <button type="submit" class="btn btn-primary" id="btnSubmit">
                            <span class="fe fe-save fe-16 mr-2"></span>Ajukan Permintaan
                        </button>
                        <a href="{{ route('transfer-requests.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Template satu baris item — di-clone JS, __INDEX__ diganti angka urut --}}
    <template id="itemTemplate">
        <div class="card shadow mb-3 item-block">
            <div class="card-header d-flex justify-content-between align-items-center py-2">
                <span class="font-weight-bold item-title">Item #__LABEL__</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-item">
                    <span class="fe fe-trash-2 mr-1"></span>Hapus
                </button>
            </div>
            <div class="card-body">
                <div class="form-row">
                    <div class="form-group col-md-5">
                        <label>Item <span class="text-danger">*</span></label>
                        <select class="form-control select2 row-item" name="items[__INDEX__][item_id]" required>
                            <option value="">-- Pilih Item --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}">
                                    {{ $item->item_no }} - {{ $item->item_desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-4">
                        <label>Ukuran Kemasan <span class="text-danger">*</span></label>
                        <select class="form-control row-perpackage" name="items[__INDEX__][requested_perpackage]" disabled
                            required>
                            <option value="">-- Pilih item dulu --</option>
                        </select>
                        <small class="form-text text-muted row-size-hint"></small>
                    </div>

                    <div class="form-group col-md-3">
                        <label>Jumlah Package <span class="text-danger">*</span></label>
                        <input type="number" step="1" min="1" class="form-control row-package"
                            name="items[__INDEX__][requested_package]" disabled required>
                        <small class="form-text text-muted row-max-hint"></small>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4 mb-0">
                        <label>Setara Berat</label>
                        <input type="text" class="form-control bg-light font-weight-bold row-weight" readonly
                            placeholder="0,00 KG">
                    </div>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        let itemIndex = 0;
        const MAX_ITEMS = 10;

        function formatNumber(n) {
            return new Intl.NumberFormat('id-ID', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            }).format(n);
        }

        function renumberItems() {
            $('#itemList .item-block').each(function(i) {
                $(this).find('.item-title').text('Item #' + (i + 1));
            });

            const count = $('#itemList .item-block').length;
            $('#itemList .btn-remove-item').toggle(count > 1);
            $('#btnAddItem').prop('disabled', count >= MAX_ITEMS);
        }

        function loadSizes($block) {
            const $item = $block.find('.row-item');
            const $size = $block.find('.row-perpackage');
            const $pkg = $block.find('.row-package');
            const $hint = $block.find('.row-size-hint');

            // Data ukuran disimpan di elemen baris ini, bukan variabel
            // global — tiap baris punya daftar ukurannya sendiri.
            $block.data('sizes', {});
            $pkg.val('').prop('disabled', true).removeClass('is-invalid');
            $block.find('.row-max-hint, .row-size-hint').text('');
            $block.find('.row-weight').val('');

            const itemId = $item.val();

            if (!itemId) {
                $size.html('<option value="">-- Pilih item dulu --</option>').prop('disabled', true);
                return;
            }

            $size.html('<option value="">-- Memuat... --</option>').prop('disabled', true);

            $.get('{{ route('transfer-requests.package-sizes') }}', {
                item_id: itemId
            }).done(function(sizes) {
                if (!sizes.length) {
                    $size.html('<option value="">-- Stok tidak tersedia --</option>');
                    $hint.html(
                        '<span class="text-danger">Tidak ada stok item ini milik department Anda di gudang IMC.</span>'
                        );
                    return;
                }

                const map = {};
                let options = '<option value="">-- Pilih Ukuran --</option>';

                sizes.forEach(function(s) {
                    map[s.qty_perpackage] = s;
                    options += `<option value="${s.qty_perpackage}">` +
                        `${s.package ?? 'Kemasan'} ${formatNumber(s.qty_perpackage)} kg — tersedia ${s.available_package} pkg` +
                        `</option>`;
                });

                $block.data('sizes', map);
                $size.html(options).prop('disabled', false);
                $hint.text(sizes.length + ' ukuran tersedia.');
            }).fail(function() {
                $size.html('<option value="">-- Gagal memuat --</option>');
                $hint.html('<span class="text-danger">Gagal memuat data stok.</span>');
            });
        }

        function selectedSize($block) {
            const sizes = $block.data('sizes') || {};
            return sizes[$block.find('.row-perpackage').val()] ?? null;
        }

        function updateWeight($block) {
            const size = selectedSize($block);
            if (!size) return;

            const $pkg = $block.find('.row-package');
            const pkg = parseFloat($pkg.val()) || 0;
            const total = pkg * parseFloat(size.qty_perpackage);

            $block.find('.row-weight').val(total > 0 ? formatNumber(total) + ' KG' : '');
            $pkg.toggleClass('is-invalid', pkg > size.available_package);
        }

        function addItem() {
            if ($('#itemList .item-block').length >= MAX_ITEMS) return;

            const html = document.getElementById('itemTemplate').innerHTML
                .replaceAll('__INDEX__', itemIndex)
                .replaceAll('__LABEL__', itemIndex + 1);

            const $block = $(html);
            $('#itemList').append($block);

            if (window.initSelect2) window.initSelect2($block);

            $block.find('.row-item').on('change', function() {
                loadSizes($block);
            });

            $block.find('.row-perpackage').on('change', function() {
                const size = selectedSize($block);
                const $pkg = $block.find('.row-package');

                if (!size) {
                    $pkg.val('').prop('disabled', true);
                    $block.find('.row-max-hint').text('');
                    return;
                }

                const exp = size.nearest_exp ?
                    new Date(size.nearest_exp).toLocaleDateString('id-ID', {
                        month: 'short',
                        year: 'numeric'
                    }) :
                    '-';

                let hint = 'Total ' + formatNumber(size.total_weight) + ' kg &middot; exp terdekat ' + exp;

                // Beri tahu kalau sebagian stok sudah dipesan request lain
                // yang belum diproses — supaya angka tersedia tidak
                // terasa janggal.
                if (size.reserved_package > 0) {
                    hint += ' &middot; <span class="text-warning">' + size.reserved_package +
                        ' pkg dipesan permintaan lain</span>';
                }

                $block.find('.row-size-hint').html(hint);
                $block.find('.row-max-hint').text('Maksimal ' + size.available_package + ' package.');

                $pkg.prop('disabled', false).attr('max', size.available_package)
                    .val('').removeClass('is-invalid').focus();
                $block.find('.row-weight').val('');
            });

            $block.find('.row-package').on('input change', function() {
                updateWeight($block);
            });

            $block.find('.btn-remove-item').on('click', function() {
                $block.remove();
                renumberItems();
            });

            itemIndex++;
            renumberItems();
        }

        $('#btnAddItem').on('click', addItem);

        $(document).ready(function() {
            addItem(); // baris pertama otomatis muncul
        });
    </script>
@endpush
