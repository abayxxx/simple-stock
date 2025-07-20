@extends('adminlte::page')

@section('title', 'Edit Produk')

@section('content_header')
<h1>Edit Produk</h1>
@stop

@section('content')
<form action="{{ route('products.update', $product) }}" method="POST">
    @csrf
    @method('PUT')
    @include('data_master.products.partials.form', ['product' => $product, 'satuanList' => $satuanList])
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('products.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop