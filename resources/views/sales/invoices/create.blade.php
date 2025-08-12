@extends('adminlte::page')

@section('title', 'Tambah Faktur Penjualan')

@section('content_header')
<h1>Tambah Faktur Penjualan</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('sales.invoices.store') }}">
            @csrf
            @include('sales.invoices.partials.form', [
            'customers' => $customers,
            'salesGroups' => $salesGroups,
            'products' => null,
            'branches' => $branches,
            'invoice' => null
            ])
            @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            <div class="mt-3">
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
                <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@stop