@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Tambah Stok Gudang</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('item-locations.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('item_id') is-invalid @enderror" name="item_id"
                                    required>
                                    <option value="">-- Pilih Item --</option>
                                    @foreach ($items as $item)
                                        <option value="{{ $item->id }}"
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
                                <label>Demander<span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('demander_id') is-invalid @enderror"
                                    name="demander_id" required>
                                    <option value="">-- Pilih Demander --</option>
                                    @foreach ($departments as $dept)
                                        <option value="{{ $dept->id }}"
                                            {{ old('demander_id') == $dept->id ? 'selected' : '' }}>
                                            {{ $dept->code }} - {{ $dept->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('demander_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Gudang <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                    name="warehouse_id" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} - {{ $wh->tag }}
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
                                    value="{{ old('vendor_lot') }}" placeholder="Contoh: VENLOT-202601">
                            </div>
                            <div class="form-group col-md-6">
                                <label>Package</label>
                                <input type="text" class="form-control uppercase" name="package"
                                    value="{{ old('package') }}" placeholder="Contoh: DRUM">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Bulan Produksi</label>
                                <input type="month" class="form-control @error('production_date') is-invalid @enderror"
                                    id="production_date" name="production_date"
                                    value="{{ old('production_date') ? \Illuminate\Support\Str::substr(old('production_date'), 0, 7) : '' }}">
                                @error('production_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Tanggal Expired <small class="text-muted">(otomatis +1 tahun)</small></label>
                                <input type="text" class="form-control" id="exp_preview" readonly
                                    placeholder="Terisi otomatis dari bulan produksi">
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Berat / Qty (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('qty_weight') is-invalid @enderror" name="qty_weight"
                                    value="{{ old('qty_weight') }}" required>
                                @error('qty_weight')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-4">
                                <label>Jumlah Kemasan (Unit)</label>
                                <input type="number" step="0.01" min="0" class="form-control" name="qty_unit"
                                    value="{{ old('qty_unit') }}" placeholder="Contoh: 8">
                            </div>
                            <div class="form-group col-md-4">
                                <label>Tanggal Diterima</label>
                                <input type="date" class="form-control" name="received_date"
                                    value="{{ old('received_date', now()->toDateString()) }}">
                            </div>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan
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
        // Preview tanggal expired = 1 tahun setelah bulan produksi
        function updateExpPreview() {
            const val = $('#production_date').val(); // format: YYYY-MM
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
        $(document).ready(updateExpPreview);
    </script>
@endpush
