@extends('adminlte::page')
@section('title', 'Pengaturan Profil')

@section('content')
<div class="row mt-3">
    <div class="col-md-6">
        <div class="card card-primary">
            <div class="card-header"><h3 class="card-title">Ubah Profil</h3></div>
            <form action="{{ route('settings.profile.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if(session('success')) <div class="alert alert-success">{{ session('success') }}</div> @endif

                    <div class="form-group">
                        <label for="name">Nama</label>
                        <input name="name" type="text" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror">
                        @error('name')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input name="email" type="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror">
                        @error('email')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Profil</button>
                </div>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-secondary">
            <div class="card-header"><h3 class="card-title">Ubah Password</h3></div>
            <form action="{{ route('settings.password.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    @if($errors->has('current_password'))
                        <div class="alert alert-danger">{{ $errors->first('current_password') }}</div>
                    @endif
                    <div class="form-group">
                        <label for="current_password">Password Lama</label>
                        <input name="current_password" type="password" class="form-control @error('current_password') is-invalid @enderror">
                    </div>
                    <div class="form-group">
                        <label for="password">Password Baru</label>
                        <input name="password" type="password" class="form-control @error('password') is-invalid @enderror">
                        @error('password')<span class="invalid-feedback d-block">{{ $message }}</span>@enderror
                    </div>
                    <div class="form-group">
                        <label for="password_confirmation">Konfirmasi Password Baru</label>
                        <input name="password_confirmation" type="password" class="form-control">
                    </div>
                </div>
                <div class="card-footer">
                    <button class="btn btn-secondary"><i class="fa fa-key"></i> Simpan Password</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
