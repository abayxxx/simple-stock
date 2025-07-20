@extends('adminlte::page')
@section('title', 'Edit ' . $title)

@section('content_header')
<h1>Tambah {{ $title }}</h1>
@stop

@section('content')
<form action="{{ route('stock.update', ['type' => $type, 'stock' => $stock->id]) }}" method="POST">
    @csrf
    @include('stocks.partials.form', ['products' => $products, 'stock' => $stock ?? null])
    <button type="submit" class="btn btn-primary">Simpan</button>
    <a href="{{ route('stock.' . $type) }}" class="btn btn-secondary">Kembali</a>
</form>
@stop