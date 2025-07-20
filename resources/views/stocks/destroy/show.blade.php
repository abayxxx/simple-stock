@extends('adminlte::page')
@section('title', 'Detail ' . $title)

@section('content_header')
<h1>Detail {{ $title }}</h1>
<a href="{{ route('stock.' . $type) }}" class="btn btn-secondary mb-3">Kembali</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        <table class="table">
            <tr>
                <th>No. Transaksi</th>
                <td>{{ $stock->kode }}</td>
            </tr>
            <tr>
                <th>Produk</th>
                <td>{{ $stock->product->nama ?? '-' }}</td>
            </tr>
            <tr>
                <th>No. Seri</th>
                <td>{{ $stock->no_seri ?? '-' }}</td>
            </tr>
            <tr>
                <th>Tanggal Expired</th>
                <td>{{ $stock->tanggal_expired ?? '-' }}</td>
            </tr>
            <tr>
                <th>Qty. Unit</th>
                <td>{{ $stock->jumlah }}</td>
            </tr>
            <tr>
                <th>Harga Net</th>
                <td>{{ number_format($stock->harga_net,2,',','.') }}</td>
            </tr>
            <tr>
                <th>Sub Total</th>
                <td>{{ number_format($stock->subtotal,2,',','.') }}</td>
            </tr>
            <tr>
                <th>Catatan</th>
                <td>{{ $stock->catatan ?? '-' }}</td>
            </tr>
            <tr>
                <th>Sisa Stok</th>
                <td>{{ $stock->sisa_stok }}</td>
            </tr>
        </table>
    </div>
</div>
@stop