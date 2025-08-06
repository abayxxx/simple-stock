@extends('adminlte::page')

@section('title', 'Edit Admin User')

@section('content_header')
<h1>Edit Admin User</h1>
@stop

@section('content')
<div class="container">
    <form method="POST" action="{{ route('management.users.update', $user->id) }}">
        @csrf
        @method('PUT')
        @include('admin.partials.form')
        <button class="btn btn-success">Update Admin User</button>
        <a href="{{ route('management.users.index') }}" class="btn btn-secondary">Kembali</a>
    </form>
</div>
@endsection