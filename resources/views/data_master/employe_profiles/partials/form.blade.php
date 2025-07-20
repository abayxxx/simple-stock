@php
$employe_profile = $employe_profile ?? null;
@endphp

<div class="form-group">
    <label>Nama Pegawai <span class="text-danger">*</span></label>
    <input name="nama" value="{{ old('nama', $employe_profile->nama ?? '') }}" class="form-control" required>
</div>
<div class="form-group">
    <label>No Telepon</label>
    <input name="no_telepon" value="{{ old('no_telepon', $employe_profile->no_telepon ?? '') }}" class="form-control">
</div>
<div class="form-group">
    <label>Email</label>
    <input name="email" value="{{ old('email', $employe_profile->email ?? '') }}" class="form-control" type="email">
</div>
<div class="form-group">
    <label>Alamat</label>
    <input name="alamat" value="{{ old('alamat', $employe_profile->alamat ?? '') }}" class="form-control">
</div>
<div class="form-group">
    <label>Catatan</label>
    <textarea name="catatan" class="form-control">{{ old('catatan', $employe_profile->catatan ?? '') }}</textarea>
</div>