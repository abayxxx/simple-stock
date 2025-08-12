@extends('adminlte::page')
@section('title', 'Tambah Retur Penjualan')
@section('content_header')
<h1>Tambah Retur Penjualan</h1>
@stop

@section('content')
<form method="POST" action="{{ route('sales.returns.store') }}">
    @csrf
    @include('sales.returns.partials.form', [
    'return' => null,
    'customers' => $customers,
    'salesGroups' => $salesGroups,
    'products' => null,
    'branches' => $branches,
    'invoices' => $invoices
    ])
</form>
@stop