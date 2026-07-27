@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Edit Stok Gudang</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('item-locations.update', $itemLocation->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('item_id') is-invalid @enderror" name="item_id"
                                    required>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}"
                                            {{ old('item_id', $itemLocation->item_id) == $item->id ? 'selected' : '' }}>
                                            {{ $item->item_no }} - {{ $item->item_desc }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('item_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Gudang <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                    name="warehouse_id" required>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('warehouse_id', $itemLocation->warehouse_id) == $wh->id ? 'selected' : '' }}>
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
                                <label>Vendor Lot</label>
                                <input type="text" class="form-control uppercase" name="vendor_lot"
                                    value="{{ old('vendor_lot', $itemLocation->vendor_lot) }}">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Package</label>
                                <input type="text" class="form-control uppercase" name="package"
                                    value="{{ old('package', $itemLocation->package) }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Bulan Produksi</label>
                                <input type="month" class="form-control @error('production_date') is-invalid @enderror"
                                    id="production_date" name="production_date"
                                    value="{{ old('production_date') ? \Illuminate\Support\Str::substr(old('production_date'), 0, 7) : $itemLocation->production_date?->format('Y-m') }}">
                                @error('production_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tanggal Expired <small class="text-muted">(otomatis +1 tahun)</small></label>
                                <input type="text" class="form-control" id="exp_preview" readonly
                                    value="{{ $itemLocation->exp_date?->translatedFormat('d F Y') }}">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Berat / Qty (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('qty_weight') is-invalid @enderror" name="qty_weight"
                                    value="{{ old('qty_weight', $itemLocation->qty_weight) }}" required>
                                @error('qty_weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Jumlah Kemasan (Unit)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="qty_unit"
                                    value="{{ old('qty_unit', $itemLocation->qty_unit) }}">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tanggal Diterima</label>
                                <input type="date" class="form-control" name="received_date"
                                    value="{{ old('received_date', $itemLocation->received_date?->toDateString()) }}">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Perbarui
                            </button>
                            <a href="{{ route('item-locations.index') }}" class="btn btn-light">Batal</a>
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
            const expDate = new Date(parseInt(year) + 1, parseInt(month) - 1, 1);
            const opts = {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            };
            $('#exp_preview').val(expDate.toLocaleDateString('id-ID', opts));
        }

        $('#production_date').on('change input', updateExpPreview);
    </script>
@endpush
