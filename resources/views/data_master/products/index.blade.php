@extends('adminlte::page')

@section('title', 'Daftar Produk')

@section('content_header')
<h1>Daftar Produk</h1>
<a href="{{ route('products.create') }}" class="btn btn-primary float-right">Tambah Produk</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered w-100" id="products-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Merk</th>
                <th>Satuan Kecil</th>
                <th>Harga Pokok Bruto</th>
                <th>Harga Jual Umum</th>
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
        $('#products-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('products.datatable') }}",
            columns: [{
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'merk',
                    name: 'merk'
                },
                {
                    data: 'satuan_kecil',
                    name: 'satuan_kecil'
                },
                {
                    data: 'hpp_bruto_kecil',
                    name: 'hpp_bruto_kecil'
                },
                {
                    data: 'harga_umum',
                    name: 'harga_umum'
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