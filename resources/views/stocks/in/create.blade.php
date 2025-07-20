@extends('adminlte::page')
@section('title', 'Tambah ' . $title)

@section('content_header')
<h1>Tambah {{ $title }}</h1>
@stop

@section('content')
<form action="{{ route('stock.store', ['type' => $type]) }}" method="POST">
    @csrf
    @include('stocks.partials.form', ['products' => $products, 'stock' => $stock ?? null, 'type' => $type])
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('stock.' . $type) }}" class="btn btn-secondary">Kembali</a>
</form>

@stop