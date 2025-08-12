@php $company_branch = $company_branch ?? null; @endphp
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
    <label>Nama Cabang <span class="text-danger">*</span></label>
    <input name="name" value="{{ old('name', $company_branch->name ?? '') }}" class="form-control" required>
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group">
    <label>Alamat</label>
    <input name="address" value="{{ old('address', $company_branch->address ?? '') }}" class="form-control">
    @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>