@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<div class="form-group mb-3">
    <label>Name</label>
    <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name ?? '') }}">
    @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email ?? '') }}">
    @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group mb-3">
    <label>Cabang</label>
    <select name="company_branch_id" class="form-control" required>
        <option value="">Pilih Cabang</option>
        @foreach($branch as $branchItem)
        <option value="{{ $branchItem->id }}" {{ (old('company_branch_id', $user->company_branch_id ?? '') == $branchItem->id) ? 'selected' : '' }}>
            {{ $branchItem->name }}
        </option>
        @endforeach
    </select>
    @error('company_branch_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
<div class="form-group mb-3">
    <label> Role</label>
    <select name="role" class="form-control" required>
        <option value="">Select Role</option>
        <option value="admin" {{ (old('role', $user->role ?? '') == 'admin') ? 'selected' : '' }}>Admin</option>
        <option value="superadmin" {{ (old('role', $user->role ?? '') == 'superadmin') ? 'selected' : '' }}>Super Admin</option>
        <option value="sales" {{ (old('role', $user->role ?? '') == 'sales') ? 'selected' : '' }}>Sales</option>
    </select>
    @error('role') <div class="invalid-feedback">{{ $message }}</div> @enderror
</div>
@if(!isset($user))
<div class="form-group mb-3">
    <label>Password</label>
    <input type="password" name="password" class="form-control" required>
</div>
<div class="form-group mb-3">
    <label>Confirm Password</label>
    <input type="password" name="password_confirmation" class="form-control" required>
</div>
@else
<div class="form-group mb-3">
    <label>Password <small>(Jangan diisi jika tidak ingin mengubah)</small></label>
    <input type="password" name="password" class="form-control">
</div>
<div class="form-group mb-3">
    <label>Confirm Password</label>
    <input type="password" name="password_confirmation" class="form-control">
</div>
@endif