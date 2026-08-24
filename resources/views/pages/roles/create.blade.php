@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Tambah Role</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('roles.store') }}" method="POST">
                        @csrf

                        <div class="form-group">
                            <label>Nama Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name') }}" placeholder="Contoh: supervisor (huruf kecil, tanpa spasi)"
                                required>
                            <small class="form-text text-muted">Hanya huruf, angka, strip, underscore. Tidak boleh
                                spasi.</small>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <label class="mb-3">Permission</label>

                        @foreach ($permissionGroups as $groupLabel => $permissions)
                            @if (count($permissions))
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <p class="small text-muted text-uppercase mb-0">{{ $groupLabel }}</p>
                                        <button type="button" class="btn btn-sm btn-link p-0 btn-toggle-group"
                                            data-group="{{ $loop->index }}">Pilih semua</button>
                                    </div>
                                    @foreach ($permissions as $perm)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input perm-check"
                                                data-group="{{ $loop->parent->index }}" id="perm_{{ $perm['name'] }}"
                                                name="permissions[]" value="{{ $perm['name'] }}"
                                                {{ in_array($perm['name'], old('permissions', [])) ? 'checked' : '' }}>
                                            <label class="custom-control-label" for="perm_{{ $perm['name'] }}">
                                                {{ $perm['label'] }}
                                            </label>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        @endforeach

                        @error('permissions')
                            <div class="text-danger small">{{ $message }}</div>
                        @enderror

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Simpan
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
    @push('scripts')
        <script>
            $('.btn-toggle-group').on('click', function() {
                const group = $(this).data('group');
                const $checks = $('.perm-check[data-group="' + group + '"]');
                const allChecked = $checks.length === $checks.filter(':checked').length;

                $checks.prop('checked', !allChecked);
                $(this).text(allChecked ? 'Pilih semua' : 'Batalkan semua');
            });
        </script>
    @endpush
@endsection
