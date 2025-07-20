<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\EmployeProfile;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class EmployeProfileController extends Controller
{
    public function index()
    {
        return view('data_master.employe_profiles.index');
    }

    public function datatable()
    {
        return DataTables::of(EmployeProfile::query())
            ->addColumn('aksi', function ($row) {
                return view('data_master.employe_profiles.partials.aksi', compact('row'))->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        return view('data_master.employe_profiles.create');
    }

    public function store(Request $request)
    {
        $data = $this->validateProfile($request);
        EmployeProfile::create($data);
        return redirect()->route('employe_profiles.index')->with('success', 'Profil pegawai berhasil ditambahkan.');
    }

    public function edit(EmployeProfile $employe_profile)
    {
        return view('data_master.employe_profiles.edit', compact('employe_profile'));
    }

    public function update(Request $request, EmployeProfile $employe_profile)
    {
        $data = $this->validateProfile($request, $employe_profile->id);
        $employe_profile->update($data);
        return redirect()->route('employe_profiles.index')->with('success', 'Profil pegawai berhasil diupdate.');
    }

    public function destroy(EmployeProfile $employe_profile)
    {
        $employe_profile->delete();
        return redirect()->route('employe_profiles.index')->with('success', 'Profil pegawai berhasil dihapus.');
    }

    public function show(EmployeProfile $employe_profile)
    {
        return view('data_master.employe_profiles.show', compact('employe_profile'));
    }

    private function validateProfile(Request $request, $id = null)
    {
        return $request->validate([
            'nama' => 'required|string|max:255',
            'no_telepon' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:100',
            'alamat' => 'nullable|string|max:255',
            'catatan' => 'nullable|string|max:500',
        ]);
    }
}
