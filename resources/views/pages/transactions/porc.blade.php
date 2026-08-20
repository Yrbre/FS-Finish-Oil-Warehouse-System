@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Supply Oil — Pemasukan (PORC)</h2>
                    <p class="text-muted mb-0">Pencatatan barang masuk dari vendor. Klik "Tambah Form" untuk input beberapa
                        transaksi sekaligus.</p>
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

            <form action="{{ route('transactions.porc.store') }}" method="POST" id="porcForm">
                @csrf

                <div id="entryList"></div>

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <button type="button" class="btn btn-outline-primary" id="btnAddEntry">
                        <span class="fe fe-plus mr-1"></span>Tambah Form PORC
                    </button>

                    <div>
                        <button type="submit" class="btn btn-primary">
                            <span class="fe fe-save fe-16 mr-2"></span>Simpan Semua
                        </button>
                        <a href="{{ route('transactions.index') }}" class="btn btn-light">Batal</a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    {{-- Template 1 blok form lengkap — di-clone via JS, __INDEX__ diganti angka urut --}}
    <template id="entryTemplate">
        <div class="card shadow mb-3 entry-block">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span class="font-weight-bold entry-title">Form PORC #__LABEL__</span>
                <button type="button" class="btn btn-sm btn-outline-danger btn-remove-entry">
                    <span class="fe fe-trash-2 mr-1"></span>Hapus Form Ini
                </button>
            </div>
            <div class="card-body">

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Item <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="entries__INDEX__item_id" name="entries[__INDEX__][item_id]"
                            required>
                            <option value="">-- Pilih Item --</option>
                            @foreach ($items as $item)
                                <option value="{{ $item->id }}" data-uom="{{ $item->item_uom }}">
                                    {{ $item->item_no }} - {{ $item->item_desc }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Demander <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="entries__INDEX__demander_id"
                            name="entries[__INDEX__][demander_id]" required>
                            <option value="">-- Pilih Demander --</option>
                            @foreach ($departments as $dept)
                                <option value="{{ $dept->id }}">{{ $dept->code }} - {{ $dept->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Gudang Tujuan <span class="text-danger">*</span></label>
                        <select class="form-control select2" id="entries__INDEX__warehouse_id"
                            name="entries[__INDEX__][warehouse_id]" required>
                            <option value="">-- Pilih Gudang --</option>
                            @foreach ($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }} - {{ $wh->tag }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Tanggal Masuk <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="entries[__INDEX__][trans_date]"
                            value="{{ now()->toDateString() }}" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Vendor Lot <span class="text-danger">*</span></label>
                        <input type="text" class="form-control uppercase" name="entries[__INDEX__][vendor_lot]"
                            placeholder="Contoh: VENLOT-202601" required>
                    </div>

                    <div class="form-group col-md-6">
                        <label>Bulan Produksi <span class="text-danger">*</span></label>
                        <input type="month" class="form-control row-production-date"
                            name="entries[__INDEX__][production_date]" required>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-4">
                        <label>Expired <small class="text-muted">(otomatis +1 tahun)</small></label>
                        <input type="text" class="form-control row-exp-preview" readonly placeholder="Terisi otomatis">
                    </div>
                    <div class="form-group col-md-4">
                        <label>Berat / Qty (KG) <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control"
                            name="entries[__INDEX__][trans_qty]" required>
                    </div>
                    <div class="form-group col-md-4">
                        <label>Jumlah Kemasan (Unit)</label>
                        <input type="number" step="0.01" min="0" class="form-control"
                            name="entries[__INDEX__][qty_unit]" placeholder="Contoh: 8">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label>Kemasan</label>
                        <select class="form-control select2" id="entries__INDEX__package" name="entries[__INDEX__][package]"
                            required>
                            <option value="BAG">BAG</option>
                            <option value="CAN">CAN</option>
                            <option value="DRUM">DRUM</option>
                            <option value="TOTE">TOTE</option>
                        </select>
                    </div>
                </div>

                <div class="form-group mb-0">
                    <label>Catatan</label>
                    <textarea class="form-control" name="entries[__INDEX__][notes]" rows="2"></textarea>
                </div>
            </div>
        </div>
    </template>
@endsection

@push('scripts')
    <script>
        let entryIndex = 0;

        function bindExpPreview($scope) {
            const $prod = $scope.find('.row-production-date');
            const $exp = $scope.find('.row-exp-preview');

            function update() {
                const val = $prod.val();
                if (!val) {
                    $exp.val('');
                    return;
                }
                const [year, month] = val.split('-');
                const exp = new Date(parseInt(year) + 1, parseInt(month) - 1, 1);
                $exp.val(exp.toLocaleDateString('id-ID', {
                    day: '2-digit',
                    month: 'long',
                    year: 'numeric'
                }));
            }

            $prod.on('change input', update);
        }

        function renumberEntries() {
            $('#entryList .entry-block').each(function(i) {
                $(this).find('.entry-title').text('Form PORC #' + (i + 1));
            });
            // tombol hapus disembunyikan kalau cuma tersisa 1 form
            $('#entryList .btn-remove-entry').toggle($('#entryList .entry-block').length > 1);
        }

        function addEntry() {
            const template = document.getElementById('entryTemplate');
            const html = template.innerHTML
                .replaceAll('__INDEX__', entryIndex)
                .replaceAll('__LABEL__', entryIndex + 1);
            const $entry = $(html);

            $('#entryList').append($entry);

            window.initSelect2($entry); // ⬅️ scope HANYA ke baris baru ini
            bindExpPreview($entry);

            $entry.find('.btn-remove-entry').on('click', function() {
                $(this).closest('.entry-block').remove();
                renumberEntries();
            });

            entryIndex++;
            renumberEntries();
        }

        $('#btnAddEntry').on('click', addEntry);

        $(document).ready(function() {
            addEntry(); // form pertama otomatis muncul saat halaman dibuka
        });
    </script>
@endpush
