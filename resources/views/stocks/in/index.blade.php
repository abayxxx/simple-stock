@extends('adminlte::page')
@section('title', $title)

@section('content_header')
<h1>{{ $title }}</h1>
<a href="{{ route('stock.create', ['type' => $type]) }}" class="btn btn-primary float-right">Tambah {{ $title }}</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
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
            ajax: '{{ route("stock.datatable", ["type" => $type]) }}',
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
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush