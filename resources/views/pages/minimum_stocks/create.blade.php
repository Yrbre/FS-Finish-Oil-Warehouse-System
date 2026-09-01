@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Tambah Minimum Stock</h2>
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
                    <form action="{{ route('minimum-stocks.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Item <span class="text-danger">*</span></label>
                            <select class="form-control select2" id="item_id" name="item_id" required>
                                <option value="">-- Pilih Item --</option>
                                @foreach ($items as $item)
                                    <option value="{{ $item->id }}" data-uom="{{ $item->item_uom }}"
                                        data-default="{{ $item->min_stock }}"
                                        {{ old('item_id') == $item->id ? 'selected' : '' }}>
                                        {{ $item->item_no }} - {{ $item->item_desc }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted" id="itemHint"></small>
                        </div>

                        <div class="form-group">
                            <label>Department <span class="text-danger">*</span></label>
                            <select class="form-control select2" name="department_id" required>
                                <option value="">-- Pilih Department --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->code }} - {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">
                                IMC tidak muncul karena hanya menyimpankan stok, bukan memilikinya.
                            </small>
                        </div>

                        <div class="form-group">
                            <label>Minimum Stock (KG) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" min="0" class="form-control" name="min_stock"
                                value="{{ old('min_stock') }}" required>
                            <small class="form-text text-muted">
                                Peringatan dikirim saat total stok department (gudang sendiri +
                                titipan di IMC) turun di bawah angka ini.
                            </small>
                        </div>

                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="is_active" name="is_active"
                                value="1" {{ old('is_active', true) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_active">Aktifkan pemantauan</label>
                        </div>

                        <div class="mt-3">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan
                            </button>
                            <a href="{{ route('minimum-stocks.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection

@push('scripts')
    <script>
        // Tampilkan nilai default dari master item sebagai acuan.
        $('#item_id').on('change', function() {
            const def = $(this).find('option:selected').data('default');
            const uom = $(this).find('option:selected').data('uom');

            $('#itemHint').text(def ?
                'Default pada master item: ' + Number(def).toLocaleString('id-ID', {
                    minimumFractionDigits: 2
                }) + ' ' + uom :
                'Item ini belum punya minimum stock default.');
        }).trigger('change');
    </script>
@endpush
