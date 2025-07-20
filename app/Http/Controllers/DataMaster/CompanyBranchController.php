<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class CompanyBranchController extends Controller
{
    public function index()
    {
        return view('data_master.company_branches.index');
    }

    public function datatable()
    {
        return DataTables::of(CompanyBranch::query())
            ->addColumn('aksi', function ($row) {
                return view('data_master.company_branches.partials.aksi', compact('row'))->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        return view('data_master.company_branches.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);
        CompanyBranch::create($data);
        return redirect()->route('company_branches.index')->with('success', 'Cabang berhasil ditambahkan.');
    }

    public function edit(CompanyBranch $company_branch)
    {
        return view('data_master.company_branches.edit', compact('company_branch'));
    }

    public function update(Request $request, CompanyBranch $company_branch)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
        ]);
        $company_branch->update($data);
        return redirect()->route('company_branches.index')->with('success', 'Cabang berhasil diupdate.');
    }

    public function destroy(CompanyBranch $company_branch)
    {
        $company_branch->delete();
        return redirect()->route('company_branches.index')->with('success', 'Cabang berhasil dihapus.');
    }

    public function show(CompanyBranch $company_branch)
    {
        return view('data_master.company_branches.show', compact('company_branch'));
    }
}
