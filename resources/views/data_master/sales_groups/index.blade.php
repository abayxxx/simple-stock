@extends('adminlte::page')

@section('title', 'Daftar Sales Group')

@section('content_header')
<h1>Daftar Sales Group</h1>
<a href="{{ route('sales_groups.create') }}" class="btn btn-primary float-right">Tambah Grup</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered w-100" id="sales-group-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Grup</th>
                <th>Jumlah Pegawai</th>
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
        $('#sales-group-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('sales_groups.datatable') }}",
            columns: [{
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'pegawai',
                    name: 'pegawai',
                    orderable: false,
                    searchable: false
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