@extends('layouts.template')

@section('content')
    <div class="row justify-content-center">
        <div class="col-12 col-md-8 col-lg-6">

            <div class="row align-items-center mb-2">
                <div class="col">
                    <h2 class="h5 page-title">Edit User</h2>
                </div>
            </div>

            <div class="card shadow">
                <div class="card-body">
                    <form action="{{ route('users.update', $user->id) }}" method="POST">
                        @csrf
                        @method('PUT')

                        <div class="form-group">
                            <label>Nama Lengkap <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" name="name"
                                value="{{ old('name', $user->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control @error('email') is-invalid @enderror" name="email"
                                value="{{ old('email', $user->email) }}" required>
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label>Password <small class="text-muted">(kosongkan jika tidak ganti)</small></label>
                                <input type="password" class="form-control @error('password') is-invalid @enderror"
                                    name="password">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="form-group col-md-6">
                                <label>Konfirmasi Password</label>
                                <input type="password" class="form-control" name="password_confirmation">
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Department <span class="text-danger">*</span></label>
                            <select class="form-control select2 @error('department_id') is-invalid @enderror"
                                name="department_id" required>
                                @foreach ($departments as $dept)
                                    <option value="{{ $dept->id }}"
                                        {{ old('department_id', $user->department_id) == $dept->id ? 'selected' : '' }}>
                                        {{ $dept->code }} - {{ $dept->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group">
                            <label>Role <span class="text-danger">*</span></label>
                            <select class="form-control @error('role') is-invalid @enderror" name="role" required>
                                @php $currentRole = $user->getRoleNames()->first(); @endphp
                                @foreach ($roles as $roleName)
                                    <option value="{{ $roleName }}"
                                        {{ old('role', $currentRole) == $roleName ? 'selected' : '' }}>
                                        {{ strtoupper($roleName) }}
                                    </option>
                                @endforeach
                            </select>
                            @error('role')
                                <div class="invalid-feedback d-block">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="form-group custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="is_transfer_approver"
                                name="is_transfer_approver" value="1"
                                {{ old('is_transfer_approver', $user->isTransferApprover()) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="is_transfer_approver">
                                Jadikan Approver Transfer (IMC)
                            </label>
                        </div>
                        <div class="custom-control custom-checkbox mb-3">
                            <input type="checkbox" class="custom-control-input" id="can_issue_receipt"
                                name="can_issue_receipt" value="1"
                                {{ old('can_issue_receipt', $user->can_issue_receipt ?? false) ? 'checked' : '' }}>
                            <label class="custom-control-label" for="can_issue_receipt">
                                Boleh membuat & mencetak Tanda Terima Barang
                            </label>
                        </div>

                        <div class="mt-4">
                            <button type="submit" class="btn btn-primary">
                                <span class="fe fe-save fe-16 mr-2"></span>Perbarui
                            </button>
                            <a href="{{ route('users.index') }}" class="btn btn-light">Batal</a>
                        </div>
                    </form>
                </div>
            </div>

        </div>
    </div>
@endsection
