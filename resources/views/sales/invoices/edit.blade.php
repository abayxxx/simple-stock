@extends('adminlte::page')

@section('title', 'Edit Faktur Penjualan')

@section('content_header')
<h1>Edit Faktur Penjualan</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('sales.invoices.update', $invoice->id) }}">
            @csrf
            @method('PUT')
            @include('sales.invoices.partials.form', [
            'customers' => $customers,
            'salesGroups' => $salesGroups,
            'products' => $products,
            'branches' => $branches,
            'invoice' => $invoice
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
                <button type="submit" class="btn btn-primary"><i class="fa fa-save"></i> Update</button>
                <a href="{{ route('sales.invoices.index') }}" class="btn btn-secondary">Kembali</a>
            </div>
        </form>
    </div>
</div>
@stop