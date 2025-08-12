<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\SalesGroup;
use App\Models\EmployeProfile;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SalesGroupController extends Controller
{
    public function index()
    {
        return view('data_master.sales_groups.index');
    }

    public function datatable()
    {
        return DataTables::of(SalesGroup::withCount('pegawai'))
            ->addColumn('pegawai', function ($row) {
                return $row->pegawai_count . ' pegawai';
            })
            ->addColumn('aksi', function ($row) {
                return view('data_master.sales_groups.partials.aksi', compact('row'))->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        $pegawai = EmployeProfile::orderBy('nama')->get();
        return view('data_master.sales_groups.create', compact('pegawai'));
    }

    public function store(Request $request)
    {
        $data = $this->validateSalesGroup($request);

        $salesGroup = SalesGroup::create($data);
        $salesGroup->pegawai()->sync($request->pegawai_ids ?? []);

        return redirect()->route('sales_groups.index')->with('success', 'Sales Group berhasil ditambahkan.');
    }

    public function edit(SalesGroup $sales_group)
    {
        $pegawai = EmployeProfile::orderBy('nama')->get();
        $sales_group->load('pegawai');
        return view('data_master.sales_groups.edit', compact('sales_group', 'pegawai'));
    }

    public function update(Request $request, SalesGroup $sales_group)
    {
        $data = $this->validateSalesGroup($request, $sales_group->id);
        $sales_group->update($data);
        $sales_group->pegawai()->sync($request->pegawai_ids ?? []);

        return redirect()->route('sales_groups.index')->with('success', 'Sales Group berhasil diupdate.');
    }

    public function destroy(SalesGroup $sales_group)
    {
        $sales_group->delete();
        return redirect()->route('sales_groups.index')->with('success', 'Sales Group berhasil dihapus.');
    }

    public function show(SalesGroup $sales_group)
    {
        $sales_group->load('pegawai');
        return view('data_master.sales_groups.show', compact('sales_group'));
    }

    private function validateSalesGroup(Request $request, $id = null)
    {
        try {
            $data = $request->validate([
                'nama' => 'required|string|max:255',
                'catatan' => 'nullable|string|max:500',
                'pegawai_ids' => 'nullable|array',
                'pegawai_ids.*' => 'exists:employe_profiles,id'
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }
        return $data;
    }
}
