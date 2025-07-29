@extends('adminlte::page')

@section('title', 'Tambah Faktur Pembelian')

@section('content_header')
<h1>Tambah Faktur Pembelian</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('purchases.invoices.store') }}">
            @csrf
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            @include('purchases.invoices.partials.form', [
            'suppliers' => $suppliers,
            'products' => $products,
            'branches' => $branches,
            'invoice' => null
            ])

            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                <a href="{{ route('purchases.invoices.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@stop