@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Edit Item</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('items.update', $item->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label for="item_no">Kode Item <span class="text-danger">*</span></label>
                            <input type="text" class="form-control uppercase @error('item_no') is-invalid @enderror"
                                id="item_no" name="item_no" value="{{ old('item_no', $item->item_no) }}" required>
                            @error('item_no')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="item_desc">Nama Item <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('item_desc') is-invalid @enderror"
                                id="item_desc" name="item_desc" value="{{ old('item_desc', $item->item_desc) }}" required>
                            @error('item_desc')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="item_glclass">GL CLASS <span class="text-danger">*</span></label>
                            <input type="text" class="form-control uppercase @error('item_glclass') is-invalid @enderror"
                                id="item_glclass" name="item_glclass" value="{{ old('item_glclass', $item->item_glclass) }}"
                                placeholder="Contoh: FINISH OIL" required>
                            @error('item_glclass')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Minimum Stock (KG)</label>
                            <input type="number" step="0.01" min="0" class="form-control" name="min_stock"
                                value="{{ old('min_stock', $item->min_stock ?? '') }}">
                            <small class="form-text text-muted">
                                Ambang peringatan per department: stok di gudang sendiri + titipan di IMC.
                                Kosongkan bila item ini tidak perlu dipantau.
                            </small>
                        </div>

                        <div class="form-group">
                            <label for="item_uom">Satuan (UOM) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control uppercase @error('item_uom') is-invalid @enderror"
                                id="item_uom" name="item_uom" value="{{ old('item_uom', $item->item_uom) }}" required>
                            @error('item_uom')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Perbarui
                            </button>
                            <a href="{{ route('items.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
