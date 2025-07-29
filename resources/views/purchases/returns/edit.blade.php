@extends('adminlte::page')
@section('title', 'Edit Retur Pembelian')
@section('content_header')
<h1>Edit Retur Pembelian</h1>
@stop

@section('content')
<form method="POST" action="{{ route('purchases.returns.update', $return->id) }}">
    @csrf @method('PUT')
    @include('purchases.returns.partials.form', [
    'return' => $return,
    'suppliers' => $suppliers,
    'products' => $products,
    'branches' => $branches,
    'invoices' => $invoices
    ])
</form>
@stop