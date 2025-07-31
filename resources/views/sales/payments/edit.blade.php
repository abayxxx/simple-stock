@extends('adminlte::page')
@section('title', 'Edit Pembayaran Penjualan')
@section('content_header')
<h1>Edit Pembayaran Penjualan</h1>
@stop

@section('content')
<form action="{{ route('sales.payments.update', $payment->id) }}" method="POST">
    @csrf
    @method('PUT')
    @include('sales.payments.partials.form', ['payment' => $payment, 'customers' => $customers, 'items' => $payment->items])
    <div class="mt-4">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
        <a href="{{ route('sales.payments.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@stop