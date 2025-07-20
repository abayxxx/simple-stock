@extends('adminlte::page')

@section('title', 'Edit Pegawai')

@section('content_header')
<h1>Edit Pegawai</h1>
@stop

@section('content')
<form action="{{ route('employe_profiles.update', $employe_profile) }}" method="POST">
    @csrf
    @method('PUT')
    @include('data_master.employe_profiles.partials.form', ['employe_profile' => $employe_profile])
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('employe_profiles.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop