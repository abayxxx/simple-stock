<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('admin.index');
    }

    public function datatable(Request $request)
    {
        $data = User::select(['id', 'name', 'email', 'role']);
        return DataTables::of($data)
            ->addColumn('aksi', function ($r) {
                // Gunakan partial agar rapi
                return view('admin.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        return view('admin.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users',
            'password' => 'required|string|min:6|confirmed',
            'is_admin' => 'boolean',
        ]);
        $data['password'] = bcrypt($data['password']);
        User::create($data);
        return redirect()->route('admin.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('admin.edit', compact('user'));
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:100|unique:users,email,' . $user->id,
            'password' => 'nullable|string|min:6|confirmed',
            'is_admin' => 'boolean',
        ]);
        if ($request->password) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return redirect()->route('admin.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
