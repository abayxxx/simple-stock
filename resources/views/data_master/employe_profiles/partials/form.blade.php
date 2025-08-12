@php
$employe_profile = $employe_profile ?? null;
@endphp
@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<div class="form-group">
    <label>Nama Pegawai <span class="text-danger">*</span></label>
    <input name="nama" value="{{ old('nama', $employe_profile->nama ?? '') }}" class="form-control" required>
    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group">
    <label>No Telepon</label>
    <input name="no_telepon" value="{{ old('no_telepon', $employe_profile->no_telepon ?? '') }}" class="form-control">
    @error('no_telepon') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group">
    <label>Email</label>
    <input name="email" value="{{ old('email', $employe_profile->email ?? '') }}" class="form-control" type="email">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group">
    <label>Alamat</label>
    <input name="alamat" value="{{ old('alamat', $employe_profile->alamat ?? '') }}" class="form-control">
    @error('alamat') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group">
    <label>Catatan</label>
    <textarea name="catatan" class="form-control">{{ old('catatan', $employe_profile->catatan ?? '') }}</textarea>
    @error('catatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>