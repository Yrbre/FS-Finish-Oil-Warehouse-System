@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Adjustment (ADJ)</h2>
                    <p class="text-muted mb-0">Koreksi stok pada lot tertentu karena kesalahan input.</p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('transactions.adj.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('item_id') is-invalid @enderror" id="item_id"
                                    name="item_id" required>
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
                                <label>Gudang <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('warehouse_id') is-invalid @enderror"
                                    id="warehouse_id" name="warehouse_id" required>
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

                        <div class="form-group">
                            <label>Lot yang Dikoreksi <span class="text-danger">*</span></label>
                            <select class="form-control @error('item_location_id') is-invalid @enderror"
                                id="item_location_id" name="item_location_id" required>
                                <option value="">-- Pilih item &amp; gudang dulu --</option>
                            </select>
                            @error('item_location_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Arah Koreksi <span class="text-danger">*</span></label>
                                <select class="form-control @error('adj_type') is-invalid @enderror" name="adj_type"
                                    required>
                                    <option value="">-- Pilih --</option>
                                    <option value="in" {{ old('adj_type') == 'in' ? 'selected' : '' }}>Tambah (+)
                                    </option>
                                    <option value="out" {{ old('adj_type') == 'out' ? 'selected' : '' }}>Kurang (−)
                                    </option>
                                </select>
                                @error('adj_type')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label>Qty Koreksi (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('trans_qty') is-invalid @enderror" name="trans_qty"
                                    value="{{ old('trans_qty') }}" required>
                                @error('trans_qty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-4">
                                <label>Tanggal <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('trans_date') is-invalid @enderror"
                                    name="trans_date" value="{{ old('trans_date', now()->toDateString()) }}" required>
                                @error('trans_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alasan Adjustment <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('notes') is-invalid @enderror" name="notes" rows="2" required>{{ old('notes') }}</textarea>
                            @error('notes')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
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
        // Muat daftar lot saat item + gudang dipilih
        function loadLots() {
            const itemId = $('#item_id').val();
            const warehouseId = $('#warehouse_id').val();
            const $lot = $('#item_location_id');

            if (!itemId || !warehouseId) {
                $lot.html('<option value="">-- Pilih item &amp; gudang dulu --</option>');
                return;
            }

            $lot.html('<option value="">Memuat...</option>');

            $.get('{{ route('transactions.get-lots') }}', {
                    item_id: itemId,
                    warehouse_id: warehouseId
                })
                .done(function(lots) {
                    if (!lots.length) {
                        $lot.html('<option value="">Tidak ada lot di gudang ini</option>');
                        return;
                    }
                    let opts = '<option value="">-- Pilih Lot --</option>';
                    lots.forEach(function(l) {
                        opts += '<option value="' + l.id + '">' + l.label + '</option>';
                    });
                    $lot.html(opts);
                });
        }

        $('#item_id, #warehouse_id').on('change', loadLots);
    </script>
@endpush
