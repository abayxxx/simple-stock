@extends('adminlte::page')

@section('title', 'Tambah Produk')

@section('content_header')
<h1>Tambah Produk</h1>
@stop

@section('content')
<form action="{{ route('products.store') }}" method="POST">
    @csrf
    @include('data_master.products.partials.form', ['satuanList' => $satuanList])
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop