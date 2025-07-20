@extends('adminlte::page')

@section('title', 'Tambah Sales Group')

@section('content_header')
<h1>Tambah Sales Group</h1>
@stop

@section('content')
<form action="{{ route('sales_groups.store') }}" method="POST">
    @csrf
    @include('data_master.sales_groups.partials.form')
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('sales_groups.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop