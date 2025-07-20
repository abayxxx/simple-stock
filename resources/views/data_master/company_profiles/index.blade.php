@extends('adminlte::page')

@section('title', 'Profil Perusahaan')

@section('content_header')
<h1>Profil Perusahaan</h1>
<a href="{{ route('company_profiles.create') }}" class="btn btn-primary float-right">Tambah Baru</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif

<div class="table-responsive">
    <table class="table table-bordered w-100" id="company-profile-table">
        <thead>
            <tr>
                <th>Kode</th>
                <th>Nama</th>
                <th>Relasi</th>
                <th>Email</th>
                <th>Telepon</th>
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
        $('#company-profile-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('company_profiles.datatable') }}",
            columns: [{
                    data: 'code',
                    name: 'code'
                },
                {
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'relationship',
                    name: 'relationship'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'phone',
                    name: 'phone'
                },
                {
                    data: 'actions',
                    name: 'actions',
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