@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Buat Transfer Request</h2>
                    <p class="text-muted mb-0">
                        Gudang asal ditentukan otomatis oleh sistem (FEFO lintas gudang) saat request di-approve.
                    </p>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('transfer-requests.store') }}" method="POST">
                        @csrf

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Item <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('item_id') is-invalid @enderror" name="item_id"
                                    required>
                                    <option value="">-- Pilih Item
                                        --</option>
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
                                <label>Gudang Tujuan <span class="text-danger">*</span></label>
                                <select class="form-control select2 @error('destination_warehouse_id') is-invalid @enderror"
                                    name="destination_warehouse_id" id="select2-destination-warehouse" required>
                                    <option value="">-- Pilih Gudang --</option>
                                    @foreach ($warehouses as $wh)
                                        <option value="{{ $wh->id }}"
                                            {{ old('destination_warehouse_id') == $wh->id ? 'selected' : '' }}>
                                            {{ $wh->name }} - {{ $wh->tag }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('destination_warehouse_id')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Jumlah / Qty (KG) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="0"
                                    class="form-control @error('requested_qty') is-invalid @enderror" name="requested_qty"
                                    value="{{ old('requested_qty') }}" required>
                                @error('requested_qty')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="form-group col-md-6">
                                <label>Tanggal Barang Harus Sampai <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('expected_date') is-invalid @enderror"
                                    name="expected_date" value="{{ old('expected_date') }}"
                                    min="{{ now()->toDateString() }}" required>
                                @error('expected_date')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="3">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-info">
                            <span class="fe fe-info fe-16 mr-2"></span>
                            Setelah request dibuat, sistem akan merekomendasikan gudang asal berdasarkan FEFO
                            (stok yang paling dekat expired). Approver (IMC) yang akan menyetujui pengiriman.
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-send fe-16 mr-2"></span>Kirim Request
                            </button>
                            <a href="{{ route('transfer-requests.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
