@extends('adminlte::page')
@section('title', 'Edit Pembayaran Pembelian')
@section('content_header')
<h1>Edit Pembayaran Pembelian</h1>
@stop

@section('content')
<form action="{{ route('purchases.payments.update', $payment->id) }}" method="POST">
    @csrf
    @method('PUT')
    @include('purchases.payments.partials.form', ['payment' => $payment, 'suppliers' => $suppliers, 'items' => $payment->items])
    <div class="mt-4">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan Perubahan</button>
        <a href="{{ route('purchases.payments.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@stop