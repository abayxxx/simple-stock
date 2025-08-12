@extends('adminlte::page')
@section('title', 'Tambah Retur Pembelian')
@section('content_header')
<h1>Tambah Retur Pembelian</h1>
@stop

@section('content')
<form method="POST" action="{{ route('purchases.returns.store') }}">
    @csrf
    @include('purchases.returns.partials.form', [
    'return' => null,
    'suppliers' => $suppliers,
    'products' => null,
    'branches' => $branches,
    'invoices' => $invoices
    ])
</form>
@stop