@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Pemakaian — Pengeluaran (CONS)</h2>
                    <p class="text-muted mb-0">Pengeluaran stok. Diambil otomatis dari lot yang paling dekat expired (FEFO).
                    </p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('transactions.cons.store') }}" method="POST">
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

                        {{-- Info stok tersedia --}}
                        <div class="alert alert-info d-none" id="stockInfo">
                            Stok tersedia: <strong id="stockValue">0</strong> <span id="stockUom"></span>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Tanggal Pemakaian <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('trans_date') is-invalid @enderror"
                                    name="trans_date" value="{{ old('trans_date', now()->toDateString()) }}" required>
                                <small class="form-text text-muted">Bisa diisi tanggal apapun (mundur maupun maju).</small>
                                @error('trans_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Berat / Qty (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('trans_qty') is-invalid @enderror" name="trans_qty"
                                    value="{{ old('trans_qty') }}" required>
                                @error('trans_qty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
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
        // Tampilkan stok tersedia saat item + gudang dipilih
        function loadStock() {
            const itemId = $('#item_id').val();
            const warehouseId = $('#warehouse_id').val();

            if (!itemId || !warehouseId) {
                $('#stockInfo').addClass('d-none');
                return;
            }

            $.get('{{ route('transactions.get-stock') }}', {
                    item_id: itemId,
                    warehouse_id: warehouseId
                })
                .done(function(res) {
                    $('#stockValue').text(new Intl.NumberFormat('id-ID', {
                        minimumFractionDigits: 2
                    }).format(res.stock));
                    $('#stockUom').text(res.uom);
                    $('#stockInfo').removeClass('d-none');
                });
        }

        $('#item_id, #warehouse_id').on('change', loadStock);
    </script>
@endpush
