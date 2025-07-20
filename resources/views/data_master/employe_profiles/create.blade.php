@extends('adminlte::page')

@section('title', 'Tambah Pegawai')

@section('content_header')
<h1>Tambah Pegawai</h1>
@stop

@section('content')
<form action="{{ route('employe_profiles.store') }}" method="POST">
    @csrf
    @include('data_master.employe_profiles.partials.form')
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('employe_profiles.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop