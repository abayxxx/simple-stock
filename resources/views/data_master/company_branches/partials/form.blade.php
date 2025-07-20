@php $company_branch = $company_branch ?? null; @endphp

<div class="form-group">
    <label>Nama Cabang <span class="text-danger">*</span></label>
    <input name="name" value="{{ old('name', $company_branch->name ?? '') }}" class="form-control" required>
</div>
<div class="form-group">
    <label>Alamat</label>
    <input name="address" value="{{ old('address', $company_branch->address ?? '') }}" class="form-control">
</div>