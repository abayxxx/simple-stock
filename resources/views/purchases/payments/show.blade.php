@extends('adminlte::page')
@section('title', 'Detail Pembayaran Pembelian')

@section('content_header')
<h1>Detail Pembayaran Pembelian</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <div class="row mb-3">
            <div class="col-md-4"><b>Kode:</b> {{ $payment->kode }}</div>
            <div class="col-md-4"><b>Tanggal:</b> {{ tanggal_indo($payment->tanggal) }}</div>
            <div class="col-md-4"><b>Supplier:</b> {{ $payment->supplier->name ?? '-' }}</div>
        </div>
        <div class="row mb-3">
            <div class="col-md-12"><b>Catatan:</b> {{ $payment->catatan }}</div>
        </div>
        <hr>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>Tipe</th>
                    <th>No Nota</th>
                    <th>Tanggal</th>
                    <th>Nilai Nota</th>
                    <th>Sisa</th>
                    <th>KAS</th>
                    <th>BANK</th>
                    <th>GIRO</th>
                    <th>CNDN</th>
                    <th>RETUR</th>
                    <th>PANJAR</th>
                    <th>LAINNYA</th>
                    <th>SUBTOTAL</th>
                    <th>Catatan</th>
                </tr>
            </thead>
            <tbody>
                @foreach($payment->items as $item)
                <tr>
                    <td>{{ $item->tipe_nota }}</td>
                    <td>
                        @if($item->tipe_nota == 'FAKTUR')
                        {{ $item->invoice->kode ?? '' }}
                        @else
                        {{ $item->return->kode ?? '' }}
                        @endif
                    </td>
                    <td>
                        @if($item->tipe_nota == 'FAKTUR')
                        {{ $item->invoice->tanggal ?? '' }}
                        @else
                        {{ $item->return->tanggal ?? '' }}
                        @endif
                    </td>
                    <td>{{ number_format($item->nilai_nota,2,',','.') }}</td>
                    <td>{{ number_format($item->sisa,2,',','.') }}</td>
                    <td>{{ number_format($item->kas,2,',','.') }}</td>
                    <td>{{ number_format($item->bank,2,',','.') }}</td>
                    <td>{{ number_format($item->giro,2,',','.') }}</td>
                    <td>{{ number_format($item->cndn,2,',','.') }}</td>
                    <td>{{ number_format($item->retur,2,',','.') }}</td>
                    <td>{{ number_format($item->panjar,2,',','.') }}</td>
                    <td>{{ number_format($item->lainnya,2,',','.') }}</td>
                    <td>{{ number_format($item->sub_total,2,',','.') }}</td>
                    <td>{{ $item->catatan }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <div class="mt-4">
            <a href="{{ route('purchases.payments.index') }}" class="btn btn-secondary">Kembali</a>
        </div>
    </div>
</div>
@stop