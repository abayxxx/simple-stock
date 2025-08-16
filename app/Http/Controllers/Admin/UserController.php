<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
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
        $data = User::select(['id', 'name', 'email', 'role', 'company_branch_id'])->with('companyBranch:id,name'); // Eager load company branch for better performance
        return DataTables::of($data)
            ->editColumn('role', function ($row) {
                return ucfirst($row->role); // Capitalize the role for better readability
            })
            ->editColumn('cabang', function ($row) {
                return $row->companyBranch ? $row->companyBranch->name : 'N/A'; // Display branch name or N/A if not set
            })
            ->addColumn('aksi', function ($r) {
                // Gunakan partial agar rapi
                return view('admin.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        $branch = CompanyBranch::all();
        return view('admin.create', compact('branch'));
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:users',
                'password' => 'required|string|min:6|confirmed',
                'role' => 'required|in:admin,sales,superadmin', // Validasi role harus admin atau user
                'company_branch_id' => 'nullable|exists:company_branches,id', // Validasi untuk company_branch_id
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }

        $data['password'] = bcrypt($data['password']);
        User::create($data);
        return redirect()->route('management.users.index')->with('success', 'User berhasil ditambahkan.');
    }

    public function edit(User $user)
    {
        $branch = CompanyBranch::all();
        $user->load('companyBranch'); // Load relasi cabang jika ada
        return view('admin.edit', compact('user', 'branch'));
    }

    public function update(Request $request, User $user)
    {
        try {
            //code...
            $data = $request->validate([
                'name' => 'required|string|max:100',
                'email' => 'required|email|max:100|unique:users,email,' . $user->id,
                'password' => 'nullable|string|min:6|confirmed',
                'role' => 'required|in:admin,sales,superadmin', // Validasi role harus admin atau user
                'company_branch_id' => 'nullable|exists:company_branches,id', // Validasi untuk company_branch_id
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }

        if ($request->password) {
            $data['password'] = bcrypt($data['password']);
        } else {
            unset($data['password']);
        }
        $user->update($data);
        return redirect()->route('management.users.index')->with('success', 'User berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();
        return back()->with('success', 'User berhasil dihapus.');
    }
}
