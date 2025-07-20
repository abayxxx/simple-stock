@extends('adminlte::page')

@section('title', 'Detail Cabang')

@section('content_header')
<h1>Detail Cabang</h1>
<a href="{{ route('company_branches.index') }}" class="btn btn-secondary mb-3">Kembali ke Daftar</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        <table class="table">
            <tr>
                <th>Kode Cabang</th>
                <td>{{ $company_branch->code }}</td>
            </tr>
            <tr>
                <th>Nama Cabang</th>
                <td>{{ $company_branch->name }}</td>
            </tr>
            <tr>
                <th>Alamat</th>
                <td>{{ $company_branch->address ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>
@stop