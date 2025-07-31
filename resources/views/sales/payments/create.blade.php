@extends('adminlte::page')
@section('title', 'Tambah Pembayaran Penjualan')
@section('content_header')
<h1>Tambah Pembayaran Penjualan</h1>
@stop

@section('content')
<form action="{{ route('sales.payments.store') }}" method="POST">
    @csrf
    @include('sales.payments.partials.form' , [
    'customers' => $customers,
    'items' => [],
    'payment' => null
    ])
    <div class="mt-4">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
        <a href="{{ route('sales.payments.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@stop

<!-- -->