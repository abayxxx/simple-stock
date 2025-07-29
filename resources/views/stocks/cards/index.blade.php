@extends('adminlte::page')
@section('title', 'Kartu Stok')
@section('content_header')
<h1>Kartu Stok</h1>
@stop

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form id="filter-form" class="row g-2 align-items-end">
            <div class="col-md-3">
                <label>Produk</label>
                <select id="select-product" name="product_id" class="form-control">
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}">{{ $p->kode }} - {{ $p->nama }}</option>
                    @endforeach
                </select>
            </div>
            <!-- <div class="col-md-2">
                <label>Lokasi</label>
                <select id="select-branch" name="lokasi_id" class="form-control">
                    <option value="">-- Semua Lokasi --</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}">{{ $b->name }}</option>
                    @endforeach
                </select>
            </div> -->
            <div class="col-md-2">
                <label>Mulai</label>
                <input type="date" id="periode_awal" name="periode_awal" class="form-control" value="{{ date('Y-m-01') }}">
            </div>
            <div class="col-md-2">
                <label>Sampai</label>
                <input type="date" id="periode_akhir" name="periode_akhir" class="form-control" value="{{ date('Y-m-d') }}">
            </div>
            <div class="col-md-2">
                <button type="button" id="btn-refresh" class="btn btn-primary"><i class="fa fa-refresh"></i> REFRESH</button>
            </div>
        </form>
    </div>
</div>

<div class="mb-2">
    <div id="summary-bar" class="p-2 border bg-light rounded" style="font-size:1.15em">
        <b>TOTAL MASUK:</b> <span id="total-masuk">0</span> &nbsp; &nbsp;
        <b>TOTAL KELUAR:</b> <span id="total-keluar">0</span> &nbsp; &nbsp;
        <b>SISA:</b> <span id="saldo-akhir">0</span>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-striped table-bordered" id="kartu-stok-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Transaksi Dari</th>
                    <th>No Transaksi</th>
                    <th>Nama</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Sisa</th>
                </tr>
            </thead>
            <tbody>
                <!-- Diisi via JS -->
            </tbody>
        </table>
    </div>
</div>
@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    function reloadKartuStok() {
        let params = {
            product_id: $('#select-product').val(),
            // lokasi_id: $('#select-branch').val(),
            periode_awal: $('#periode_awal').val(),
            periode_akhir: $('#periode_akhir').val(),
        };
        $.get("{{ route('stocks.cards.datatable') }}", params, function(res) {
            let rows = '';
            res.data.forEach(r => {
                rows += `<tr>
                <td>${r.tanggal}</td>
                <td>${r.transaksi_dari}</td>
                <td>${r.no_transaksi}</td>
                <td>${r.nama}</td>
                <td>${r.masuk}</td>
                <td>${r.keluar}</td>
                <td>${r.sisa}</td>
            </tr>`;
            });
            $('#kartu-stok-table tbody').html(rows);
            $('#total-masuk').text(res.total_masuk + ' ' + (res.satuan || ''));
            $('#total-keluar').text(res.total_keluar + ' ' + (res.satuan || ''));
            $('#saldo-akhir').text(res.saldo_akhir + ' ' + (res.satuan || ''));
        });
    }
    $('#btn-refresh').on('click', reloadKartuStok);
    $('#filter-form select, #filter-form input').on('change', reloadKartuStok);
    $(function() {
        reloadKartuStok();
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush