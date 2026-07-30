@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Supply Oil — Pemasukan (PORC)</h2>
                    <p class="text-muted mb-0">Pencatatan barang masuk dari vendor.</p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('transactions.porc.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('item_id') is-invalid @enderror" name="item_id"
                                    required>
                                    <option value="">-- Pilih Item --</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}" data-uom="{{ $item->item_uom }}"
                                            {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                            {{ $item->item_no }} - {{ $item->item_desc }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('item_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Gudang Tujuan <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                    name="warehouse_id" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('warehouse_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tanggal Masuk <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('trans_date') is-invalid @enderror"
                                    name="trans_date" value="{{ old('trans_date', now()->toDateString()) }}" required>
                                <small class="form-text text-muted">Bisa diisi tanggal apapun (mundur maupun maju).</small>
                                @error('trans_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Vendor Lot <span class="text-danger">*</span></label>
                                <input type="text"
                                    class="form-control uppercase @error('vendor_lot') is-invalid @enderror"
                                    name="vendor_lot" value="{{ old('vendor_lot') }}" placeholder="Contoh: VENLOT-202601"
                                    required>
                                @error('vendor_lot')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Bulan Produksi <span class="text-danger">*</span></label>
                                <input type="month" class="form-control @error('production_date') is-invalid @enderror"
                                    id="production_date" name="production_date"
                                    value="{{ old('production_date') ? \Illuminate\Support\Str::substr(old('production_date'), 0, 7) : '' }}"
                                    required>
                                @error('production_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Expired <small class="text-muted">(otomatis +1 tahun)</small></label>
                                <input type="text" class="form-control" id="exp_preview" readonly
                                    placeholder="Terisi otomatis">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Berat / Qty (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('trans_qty') is-invalid @enderror" name="trans_qty"
                                    value="{{ old('trans_qty') }}" required>
                                @error('trans_qty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Jumlah Kemasan (Unit)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="qty_unit"
                                    value="{{ old('qty_unit') }}" placeholder="Contoh: 8">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Package</label>
                                <input type="text" class="form-control uppercase" name="package"
                                    value="{{ old('package') }}" placeholder="Contoh: DRUM">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes') }}</textarea>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan
                            </button>
                            <a href="{{ route('transactions.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function updateExpPreview() {
            const val = $('#production_date').val();
            if (!val) {
                $('#exp_preview').val('');
                return;
            }
            const [year, month] = val.split('-');
            const exp = new Date(parseInt(year) + 1, parseInt(month) - 1, 1);
            $('#exp_preview').val(exp.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }));
        }
        $('#production_date').on('change input', updateExpPreview);
        $(document).ready(updateExpPreview);
    </script>
@endpush
