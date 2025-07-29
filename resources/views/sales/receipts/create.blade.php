@extends('adminlte::page')
@section('title', 'Tanda Terima Penjualan')
@section('content_header')
    <h1>Buat Tanda Terima Penjualan</h1>
@stop
@section('content')
    @include('sales.receipts.partials.form', [
        'receipt' => null,
        'customers' => $customers,
        'employees' => $employees,
    ])
@stop
