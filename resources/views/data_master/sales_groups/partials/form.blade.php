@php
$sales_group = $sales_group ?? null;
@endphp

<div class="form-group">
    <label>Nama Grup <span class="text-danger">*</span></label>
    <input name="nama" value="{{ old('nama', $sales_group->nama ?? '') }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Catatan</label>
    <textarea name="catatan" class="form-control">{{ old('catatan', $sales_group->catatan ?? '') }}</textarea>
</div>
<div class="form-group">
    <label>Pilih Pegawai</label>
    <select name="pegawai_ids[]" class="form-control" multiple>
        @foreach($pegawai as $emp)
        <option value="{{ $emp->id }}"
            {{ in_array($emp->id, old('pegawai_ids', isset($sales_group) ? $sales_group->pegawai->pluck('id')->toArray() : [])) ? 'selected' : '' }}>
            {{ $emp->nama }} ({{ $emp->code }})
        </option>
        @endforeach
    </select>
    <small class="form-text text-muted">Tekan Ctrl (atau Cmd di Mac) untuk memilih lebih dari satu pegawai.</small>
</div>