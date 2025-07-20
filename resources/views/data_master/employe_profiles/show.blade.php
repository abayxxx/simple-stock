@extends('adminlte::page')

@section('title', 'Detail Pegawai')

@section('content_header')
<h1>Detail Pegawai</h1>
<a href="{{ route('employe_profiles.index') }}" class="btn btn-secondary mb-3">Kembali ke Daftar</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        <table class="table">
            <tr>
                <th>Kode Pegawai</th>
                <td>{{ $employe_profile->code }}</td>
            </tr>
            <tr>
                <th>Nama Pegawai</th>
                <td>{{ $employe_profile->nama }}</td>
            </tr>
            <tr>
                <th>No Telepon</th>
                <td>{{ $employe_profile->no_telepon ?? '-' }}</td>
            </tr>
            <tr>
                <th>Email</th>
                <td>{{ $employe_profile->email ?? '-' }}</td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td>{{ $employe_profile->alamat ?? '-' }}</td>
            </tr>
            <tr>
                <th>Catatan</th>
                <td>{{ $employe_profile->catatan ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>
@stop