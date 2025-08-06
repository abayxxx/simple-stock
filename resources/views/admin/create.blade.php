@extends('adminlte::page')

@section('title', 'Tambah Admin User')

@section('content_header')
<h1>Tambah Admin User</h1>
@stop

@section('content')
<div class="container">
    <form method="POST" action="{{ route('management.users.store') }}">
        @csrf
        @include('admin.partials.form')
        <button class="btn btn-success">Buat Admin User</button>
        <a href="{{ route('management.users.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection