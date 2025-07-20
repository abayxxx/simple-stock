@extends('adminlte::page')

@section('title', 'Tambah Cabang')

@section('content_header')
<h1>Tambah Cabang</h1>
@stop

@section('content')
<form action="{{ route('company_branches.store') }}" method="POST">
    @csrf
    @include('data_master.company_branches.partials.form')
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('company_branches.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop