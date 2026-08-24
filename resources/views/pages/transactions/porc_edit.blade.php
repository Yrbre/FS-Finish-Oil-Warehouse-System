@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Edit Supply Oil (PORC)</h2>
                    <p class="text-muted mb-0">
                        Koreksi kesalahan input. Untuk mencatat selisih fisik hasil stock opname,
                        gunakan Adjustment.
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

            @php
                $isTouched = $lot->isTouched();
                $consumed = $lot->consumed_weight;
            @endphp

            @if ($isTouched)
                <div class="alert alert-warning">
                    <strong>Qty terkunci.</strong>
                    Stok lot ini sudah terpakai {{ number_format($consumed, 2, ',', '.') }} kg
                    (sisa {{ number_format((float) $lot->qty_weight, 2, ',', '.') }} kg dari
                    {{ number_format((float) $lot->initial_weight, 2, ',', '.') }} kg).
                    Ukuran dan jumlah kemasan tidak dapat diubah — gunakan
                    <strong>Adjustment</strong> untuk mengoreksi selisihnya.
                </div>
            @endif

            <div class="card shadow mb-4">
                <div class="card-header">
                    <span class="font-weight-bold">{{ $transaction->receiving_lot }}</span>
                    <span class="text-muted ml-2">
                        {{ $transaction->item_no }} — {{ $transaction->item_desc }}
                    </span>
                </div>
                <div class="card-body">
                    <form action="{{ route('transactions.porc.update', $transaction->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        {{-- Tidak dapat diubah: mengubahnya berarti memindahkan
                             stok, bukan mengoreksi input --}}
                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label class="text-muted">Gudang</label>
                                <input type="text" class="form-control-plaintext"
                                    value="{{ $transaction->warehouse->name }} - {{ $transaction->warehouse->tag }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="text-muted">Demander</label>
                                <input type="text" class="form-control-plaintext"
                                    value="{{ $transaction->demander?->code }} - {{ $transaction->demander?->name }}"
                                    readonly>
                            </div>
                            <div class="form-group col-md-4">
                                <label class="text-muted">Tanggal Masuk</label>
                                <input type="text" class="form-control-plaintext"
                                    value="{{ $transaction->trans_date->format('d-m-Y') }}" readonly>
                            </div>
                        </div>

                        <hr>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Vendor Lot <span class="text-danger">*</span></label>
                                <input type="text" class="form-control uppercase" name="vendor_lot"
                                    value="{{ old('vendor_lot', $transaction->vendor_lot) }}" required>
                            </div>

                            <div class="form-group col-md-6">
                                <label>Bulan Produksi <span class="text-danger">*</span></label>
                                <input type="month" class="form-control" id="productionDate" name="production_date"
                                    value="{{ old('production_date', $transaction->production_date?->format('Y-m')) }}"
                                    required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Expired <small class="text-muted">(otomatis +1 tahun)</small></label>
                                <input type="text" class="form-control" id="expPreview" readonly>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Jenis Kemasan <span class="text-danger">*</span></label>
                                <select class="form-control" name="package" required>
                                    @foreach (['BAG', 'CAN', 'DRUM', 'TOTE'] as $pkg)
                                        <option value="{{ $pkg }}"
                                            {{ old('package', $transaction->package) === $pkg ? 'selected' : '' }}>
                                            {{ $pkg }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-4">
                                <label>Isi per Kemasan (KG) @unless ($isTouched)
                                        <span class="text-danger">*</span>
                                    @endunless
                                </label>
                                <input type="number" step="0.0001" min="0.0001" class="form-control" id="perPackage"
                                    name="qty_perpackage"
                                    value="{{ old('qty_perpackage', (float) $transaction->qty_perpackage) }}"
                                    {{ $isTouched ? 'disabled' : 'required' }}>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Jumlah Kemasan @unless ($isTouched)
                                        <span class="text-danger">*</span>
                                    @endunless
                                </label>
                                <input type="number" step="1" min="1" class="form-control" id="qtyPackage"
                                    name="qty_package" value="{{ old('qty_package', (int) $transaction->qty_package) }}"
                                    {{ $isTouched ? 'disabled' : 'required' }}>
                            </div>

                            <div class="form-group col-md-4">
                                <label>Total Berat</label>
                                <input type="text" class="form-control bg-light font-weight-bold" id="totalWeight"
                                    readonly>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Alasan Perubahan <span class="text-danger">*</span></label>
                            <textarea class="form-control" name="edit_reason" rows="2" required
                                placeholder="Contoh: Salah input jumlah drum, seharusnya 5 bukan 2">{{ old('edit_reason') }}</textarea>
                            <small class="text-muted">Tercatat sebagai jejak audit.</small>
                        </div>

                        <div class="form-group">
                            <label>Catatan</label>
                            <textarea class="form-control" name="notes" rows="2">{{ old('notes', $transaction->notes) }}</textarea>
                        </div>

                        @if ($transaction->isEdited())
                            <div class="alert alert-light border">
                                <small class="text-muted">
                                    Terakhir diedit {{ $transaction->edited_at->format('d-m-Y H:i') }}
                                    oleh {{ $transaction->editor?->name ?? '-' }}
                                    @if ($transaction->edit_reason)
                                        — "{{ $transaction->edit_reason }}"
                                    @endif
                                </small>
                            </div>
                        @endif

                        <div class="d-flex justify-content-end">
                            <a href="{{ route('transactions.index') }}" class="btn btn-light mr-2">Batal</a>
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan Perubahan
                            </button>
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
            const val = $('#productionDate').val();
            if (!val) return $('#expPreview').val('');

            const [year, month] = val.split('-');
            const exp = new Date(parseInt(year) + 1, parseInt(month) - 1, 1);

            $('#expPreview').val(exp.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'long',
                year: 'numeric'
            }));
        }

        function updateTotalWeight() {
            const per = parseFloat($('#perPackage').val()) || 0;
            const pkg = parseFloat($('#qtyPackage').val()) || 0;
            const total = per * pkg;

            $('#totalWeight').val(total > 0 ?
                total.toLocaleString('id-ID', {
                    minimumFractionDigits: 2,
                    maximumFractionDigits: 2
                }) + ' KG' :
                '');
        }

        $(document).ready(function() {
            $('#productionDate').on('change input', updateExpPreview);
            $('#perPackage, #qtyPackage').on('input change', updateTotalWeight);

            updateExpPreview();
            updateTotalWeight();
        });
    </script>
@endpush
