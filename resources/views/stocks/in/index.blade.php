@extends('adminlte::page')
@section('title', $title)

@section('content_header')
<h1>{{ $title }}</h1>

@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="row mb-3">
    <a href="{{ route('stock.create', ['type' => $type]) }}" class="btn btn-primary float-right">Tambah {{ $title }}</a>
    <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                <input type="date" id="periode_awal" class="form-control" style="min-width:140px">
                <span class="text-nowrap">s/d</span>
                <input type="date" id="periode_akhir" class="form-control" style="min-width:140px">
            </div>
 <a href="#" class="btn btn-success" id="export-btn" target="_blank">
        <i class="fa fa-file-excel"></i>
  Export Excel
</a>
</div>
<div class="table-responsive">
    <table class="table table-bordered w-100" id="stock-table">
        <thead>
            <tr>
                <th>No.</th>
                <th>Produk</th>
                <th>Tanggal</th>
                <th>Status</th>
                <th>Jml. Item</th>
                <th>Total Qty.</th>
                <th>Total Nilai</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        $('#stock-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: '{{ route("stock.datatable", ["type" => $type]) }}',
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                }
            },
            columns: [{
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'product.nama',
                    name: 'product.nama'
                },
                {
                    data: 'tanggal',
                    name: 'tanggal'
                },
                {
                    data: 'status',
                    name: 'status'
                },
                {
                    data: 'jumlah_item',
                    name: 'jumlah_item'
                },
                {
                    data: 'jumlah',
                    name: 'jumlah'
                },
                {
                    data: 'subtotal',
                    name: 'subtotal'
                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false
                },
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.8/i18n/id.json'
            }
        });

        $('#periode_awal, #periode_akhir').on('change', function() {
            $('#stock-table').DataTable().ajax.reload();
        });

        function buildExportUrl() {
            let periodeAwal = $('#periode_awal').val();
            let periodeAkhir = $('#periode_akhir').val();

            return "{{ route('stocks.export', ['type' => $type]) }}" + "?periode_awal=" + periodeAwal + "&periode_akhir=" + periodeAkhir;
        }
        $('#export-btn').attr('href', buildExportUrl());
        $('#periode_awal, #periode_akhir').on('change', function() {
            $('#export-btn').attr('href', buildExportUrl());
        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush