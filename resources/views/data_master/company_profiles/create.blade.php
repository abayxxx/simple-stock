@extends('adminlte::page')

@section('title', 'Tambah Profil Perusahaan')

@section('content_header')
<h1>Tambah Profil Perusahaan</h1>
@stop

@section('content')
<form action="{{ route('company_profiles.store') }}" method="POST">
    @csrf
    @include('data_master.company_profiles.partials.form')
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('company_profiles.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop