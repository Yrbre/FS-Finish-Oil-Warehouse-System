@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-10 col-lg-8">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Edit Role</h2>
                    @if ($isProtected)
                        <p class="text-muted mb-0 small">
                            <span class="fe fe-lock fe-14 mr-1"></span>
                            Role ini adalah role inti sistem — namanya tidak bisa diubah, tapi permission tetap bisa
                            disesuaikan.
                        </p>
                    @endif
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('roles.update', $role->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Nama Role <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $role->name) }}" {{ $isProtected ? 'readonly' : '' }} required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <hr>

                        <label class="mb-3">Permission</label>

                        @foreach ($permissionGroups as $groupLabel => $permissions)
                            @if (count($permissions))
                                <div class="mb-3">
                                    <p class="small text-muted text-uppercase mb-2">{{ $groupLabel }}</p>
                                    @foreach ($permissions as $perm)
                                        <div class="custom-control custom-checkbox mb-1">
                                            <input type="checkbox" class="custom-control-input"
                                                id="perm_{{ $perm['name'] }}" name="permissions[]"
                                                value="{{ $perm['name'] }}"
                                                {{ in_array($perm['name'], old('permissions', $rolePermissions)) ? 'checked' : '' }}>
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
                                <span class="fe fe-save fe-16 mr-2"></span>Perbarui
                            </button>
                            <a href="{{ route('roles.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
