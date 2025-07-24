@extends('adminlte::page')

@section('title', 'Retur Penjualan')

@section('content_header')
<h1>Retur Penjualan</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <div class="mb-3 row">
            <div class="col-md-4">
                <b>No Faktur Retur:</b> {{ $return->kode }}<br>
                <b>Tanggal:</b> {{ tanggal_indo($return->tanggal) }}<br>
                <b>Customer:</b> {{ $return->customer->name ?? '-' }}<br>
                <b>Sales Group:</b> {{ $return->salesGroup->nama ?? '-' }}<br>
            </div>
            <div class="col-md-4">
                <b>Status:</b> {{ $return->is_tunai ? 'Tunai' : 'Kredit' }}<br>
                <b>No PO:</b> {{ $return->no_po }}<br>
                <b>Jatuh Tempo:</b> {{ $return->jatuh_tempo ? tanggal_indo($return->jatuh_tempo) : '-' }}<br>
                <b>Catatan:</b> {{ $return->catatan }}<br>
            </div>
        </div>

        <div>
            <a href="{{ route('sales.returns.print', $return->id) }}" target="_blank" class="btn btn-info">
                <i class="fa fa-print"></i> Print / Export
            </a>
        </div>

        <h5 class="mt-4">Detail Barang</h5>
        <div class="table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-light">
                    <tr>
                        <th>Produk</th>
                        <th>No Seri</th>
                        <th>Expired</th>
                        <th>Qty</th>
                        <th>Harga</th>
                        <th>Diskon 1<br>% / Rp</th>
                        <th>Diskon 2<br>% / Rp</th>
                        <th>Diskon 3<br>% / Rp</th>
                        <th>Subtotal<br>Sblm Diskon</th>
                        <th>Total Diskon<br>Item</th>
                        <th>Subtotal<br>Sebelum PPN</th>
                        <th>PPN (%)</th>
                        <th>Subtotal<br>Setelah PPN</th>
                        <th>Catatan</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($return->items as $item)
                    <tr>
                        <td>{{ $item->product->nama ?? '-' }}</td>
                        <td>{{ $item->no_seri }}</td>
                        <td>{{ $item->tanggal_expired }}</td>
                        <td class="text-end">{{ $item->qty }}</td>
                        <td class="text-end">{{ number_format($item->harga_satuan,2,',','.') }}</td>
                        <td class="text-end">
                            {{ number_format($item->diskon_1_persen ?? 0, 2, ',', '.') }}%
                            <br>
                            Rp {{ number_format($item->diskon_1_rupiah ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($item->diskon_2_persen ?? 0, 2, ',', '.') }}%
                            <br>
                            Rp {{ number_format($item->diskon_2_rupiah ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="text-end">
                            {{ number_format($item->diskon_3_persen ?? 0, 2, ',', '.') }}%
                            <br>
                            Rp {{ number_format($item->diskon_3_rupiah ?? 0, 2, ',', '.') }}
                        </td>
                        <td class="text-end">{{ number_format($item->sub_total_sblm_disc,2,',','.') }}</td>
                        <td class="text-end">{{ number_format($item->total_diskon_item,2,',','.') }}</td>
                        <td class="text-end">{{ number_format($item->sub_total_sebelum_ppn,2,',','.') }}</td>
                        <td class="text-end">{{ number_format($item->ppn_persen,2,',','.') }}</td>
                        <td class="text-end">{{ number_format($item->sub_total_setelah_disc,2,',','.') }}</td>
                        <td>{{ $item->catatan }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="row mt-4 mb-2 g-3">
            <div class="col-md-3"><b>Subtotal:</b> Rp {{ number_format($return->subtotal,2,',','.') }}</div>
            <div class="col-md-3"><b>Diskon Item:</b> Rp {{ number_format($return->items->sum('total_diskon_item') ?? 0,2,',','.') }}</div>
            <div class="col-md-3"><b>Diskon Faktur:</b> {{ $return->diskon_faktur }} %</div>
            <div class="col-md-3"><b>Diskon PPN:</b> {{ $return->diskon_ppn }} %</div>
            <div class="col-md-3"><b>Grand Total:</b> Rp {{ number_format($return->grand_total,2,',','.') }}</div>
            <div class="col-md-3"><b>Total Retur:</b> Rp {{ number_format($return->total_retur,2,',','.') }}</div>
            <div class="col-md-3"><b>Total Bayar:</b> Rp {{ number_format($return->total_bayar,2,',','.') }}</div>
            <div class="col-md-3"><b>Sisa Tagihan:</b> Rp {{ number_format($return->sisa_tagihan,2,',','.') }}</div>
        </div>
        <a href="{{ route('returns.index') }}" class="btn btn-secondary mt-4">Kembali</a>
    </div>
</div>
@stop