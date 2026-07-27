@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Tambah Gudang</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('warehouses.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label for="name">Nama Gudang <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" id="name"
                                name="name" value="{{ old('name') }}" placeholder="Contoh: Gudang Finish Oil" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="tag">Tag Gudang<span class="text-danger">*</span></label>
                            <input type="text" class="form-control uppercase @error('tag') is-invalid @enderror"
                                id="tag" name="tag" value="{{ old('tag') }}" placeholder="Contoh: 1" required>
                            @error('tag')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label for="department_id">Department <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('department_id') is-invalid @enderror"
                                id="department_id" name="department_id" required>
                                <option value="">-- Pilih Department --</option>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id') == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->code }} - {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan
                            </button>
                            <a href="{{ route('warehouses.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
