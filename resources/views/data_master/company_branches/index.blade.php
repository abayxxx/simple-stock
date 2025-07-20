@extends('adminlte::page')

@section('title', 'Daftar Cabang')

@section('content_header')
<h1>Daftar Cabang</h1>
<a href="{{ route('company_branches.create') }}" class="btn btn-primary float-right">Tambah Cabang</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="table-responsive">
    <table class="table table-bordered w-100" id="branch-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama Cabang</th>
                <th>Alamat</th>
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
        $('#branch-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('company_branches.datatable') }}",
            columns: [{
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'address',
                    name: 'address'
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