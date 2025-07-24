@extends('adminlte::page')
@section('title', 'Edit Retur Penjualan')
@section('content_header')
<h1>Edit Retur Penjualan</h1>
@stop

@section('content')
<form method="POST" action="{{ route('returns.update', $return->id) }}">
    @csrf @method('PUT')
    @include('sales.returns.partials.form', [
    'return' => $return,
    'customers' => $customers,
    'salesGroups' => $salesGroups,
    'products' => $products,
    'branches' => $branches,
    'invoices' => $invoices
    ])
</form>
@stop