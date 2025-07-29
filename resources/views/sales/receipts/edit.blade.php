@extends('adminlte::page')
@section('title', 'Edit Tanda Terima Penjualan')
@section('content_header')
    <h1>Edit Tanda Terima Penjualan</h1>
@stop
@section('content')
    @include('sales.receipts.partials.form', [
        'receipt' => $receipt,
        'customers' => $customers,
        'employees' => $employees,
        'availableInvoices' => $availableInvoices,
        'items' => $receipt->receiptItems,
        'mode' => 'edit'
    ])
@stop
