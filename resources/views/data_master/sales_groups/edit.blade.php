@extends('adminlte::page')

@section('title', 'Edit Sales Group')

@section('content_header')
<h1>Edit Sales Group</h1>
@stop

@section('content')
<form action="{{ route('sales_groups.update', $sales_group) }}" method="POST">
    @csrf
    @method('PUT')
    @include('data_master.sales_groups.partials.form', ['sales_group' => $sales_group])
    <button type="submit" class="btn btn-primary">Update</button>
    <a href="{{ route('sales_groups.index') }}" class="btn btn-secondary">Kembali</a>
</form>
@stop