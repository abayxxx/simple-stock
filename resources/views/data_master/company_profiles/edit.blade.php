@extends('adminlte::page')

@section('title', 'Edit Profil Perusahaan')

@section('content_header')
<h1>Edit Profil Perusahaan</h1>
@stop

@section('content')
<form action="{{ route('company_profiles.update', $company_profile) }}" method="POST">
    @csrf
    @method('PUT')
    @include('company_profiles.partials.form', ['profile' => $company_profile])
    <button type="submit" class="btn btn-primary">Perbarui</button>
    <a href="{{ route('company_profiles.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop