@extends('adminlte::page')

@section('title', 'Daftar Pegawai')

@section('content_header')
<h1>Daftar Pegawai</h1>
<a href="{{ route('employe_profiles.create') }}" class="btn btn-primary float-right">Tambah Pegawai</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered w-100" id="pegawai-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>No Telepon</th>
                <th>Email</th>
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
        $('#pegawai-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('employe_profiles.datatable') }}",
            columns: [{
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'no_telepon',
                    name: 'no_telepon'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'alamat',
                    name: 'alamat'
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