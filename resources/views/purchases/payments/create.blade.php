@extends('adminlte::page')
@section('title', 'Tambah Pembayaran Pembelian')
@section('content_header')
<h1>Tambah Pembayaran Pembelian</h1>
@stop

@section('content')
<form action="{{ route('purchases.payments.store') }}" method="POST">
    @csrf
    @include('purchases.payments.partials.form' , [
    'suppliers' => $suppliers,
    'items' => [],
    'payment' => null
    ])
    <div class="mt-4">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
        <a href="{{ route('purchases.payments.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>
@stop

<!-- -->