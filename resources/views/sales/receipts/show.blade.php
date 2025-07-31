@extends('adminlte::page')
@section('title', 'Detail Tanda Terima Penjualan')
@section('content_header')
<h1>Detail Tanda Terima Penjualan</h1>
@stop
@section('content')
<div class="card">
    <div class="card-body">
        <div class="mb-3 row">
            <div class="col-md-4">
                <b>Kode:</b> {{ $receipt->kode }}<br>
                <b>Tanggal:</b> {{ tanggal_indo($receipt->tanggal) }}<br>
                <b>Customer:</b> {{ $receipt->customer->name ?? '-' }}<br>
            </div>
            <div class="col-md-4">
                <b>Penagih:</b> {{ $receipt->collector->nama ?? '-' }}<br>
                <b>Catatan:</b> {{ $receipt->catatan ?? '-' }}
            </div>
        </div>

        <div>
            <a href="{{ route('sales.receipts.print', $receipt->id) }}" target="_blank" class="btn btn-info">
                <i class="fa fa-print"></i> Print / Export
            </a>
        </div>

        <h5 class="mt-4">Detail Faktur Diterima</h5>
        <table class="table table-bordered table-striped">
            <thead>
                <tr>
                    <th>No Faktur</th>
                    <th>Tanggal</th>
                    <th>Total Faktur</th>
                    <th>Total Retur</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($receipt->receiptItems as $item)
                <tr>
                    <td>{{ $item->invoice->kode ?? '-' }}</td>
                    <td>{{ $item->invoice->tanggal ? tanggal_indo($item->invoice->tanggal) : '-' }}</td>
                    <td class="text-end">{{ number_format($item->total_faktur, 2, ',', '.') }}</td>
                    <td class="text-end">{{ number_format($item->total_retur, 2, ',', '.') }}</td>
                    <td>{{ $item->catatan }}</td>
                </tr>
                @endforeach

                <tr>
                    <th class="text-end" colspan="4">Total Diterima</th>
                    <td class="text-end total-diterima" colspan="2">{{ number_format($receipt->total_faktur, 2, ',', '.') }}</td>
                </tr>


            </tbody>
        </table>
        <div class="mt-4">
            <a href="{{ route('sales.receipts.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@stop