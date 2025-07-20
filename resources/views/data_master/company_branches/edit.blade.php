@extends('adminlte::page')

@section('title', 'Edit Cabang')

@section('content_header')
<h1>Edit Cabang</h1>
@stop

@section('content')
<form action="{{ route('company_branches.update', $company_branch) }}" method="POST">
    @csrf
    @method('PUT')
    @include('data_master.company_branches.partials.form', ['company_branch' => $company_branch])
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('company_branches.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop