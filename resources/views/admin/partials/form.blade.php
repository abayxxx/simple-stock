<div class="form-group mb-3">
    <label>Name</label>
    <input type="text" name="name" class="form-control" required value="{{ old('name', $user->name ?? '') }}">
</div>
<div class="form-group mb-3">
    <label>Email</label>
    <input type="email" name="email" class="form-control" required value="{{ old('email', $user->email ?? '') }}">
</div>
<div class="form-group mb-3">
    <label> Role</label>
    <select name="role" class="form-control" required>
        <option value="">Select Role</option>
        <option value="admin" {{ (old('role', $user->role ?? '') == 'admin') ? 'selected' : '' }}>Admin</option>
        <option value="superadmin" {{ (old('role', $user->role ?? '') == 'superadmin') ? 'selected' : '' }}>Super Admin</option>
    </select>
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