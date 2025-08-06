@extends('adminlte::page')

@section('title', 'Daftar Admin Users')

@section('content_header')
<h1>Daftar Admin User</h1>
<a href="{{ route('management.users.create') }}" class="btn btn-primary float-right mb-3">Tambah Admin User</a>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="container table-responsive">
    <table class="table table-bordered" id="users-table">
        <thead>
            <tr>
                <th>Name</th>
                <th>Email</th>
                <th>Role</th>
                <th>Aksi</th>
            </tr>
        </thead>
    </table>
</div>
@endsection

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script>
    $(function() {
        $('#users-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("management.users.datatable") }}',
            columns: [{
                    data: 'name',
                    name: 'name'
                },
                {
                    data: 'email',
                    name: 'email'
                },
                {
                    data: 'role',
                    name: 'role'
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