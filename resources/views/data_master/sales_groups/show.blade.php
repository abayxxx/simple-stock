@extends('adminlte::page')

@section('title', 'Detail Sales Group')

@section('content_header')
<h1>Detail Sales Group</h1>
<a href="{{ route('sales_groups.index') }}" class="btn btn-secondary mb-3">Kembali ke Daftar</a>
@stop

@section('content')
<div class="row">
    <div class="col-md-6">
        <table class="table">
            <tr>
                <th>Kode Grup</th>
                <td>{{ $sales_group->kode }}</td>
            </tr>
            <tr>
                <th>Nama Grup</th>
                <td>{{ $sales_group->nama }}</td>
            </tr>
            <tr>
                <th>Catatan</th>
                <td>{{ $sales_group->catatan ?? '-' }}</td>
            </tr>
        </table>
    </div>
</div>
<div class="mt-4">
    <h4>Daftar Anggota Pegawai:</h4>
    @if($sales_group->pegawai->count())
    <ul>
        @foreach($sales_group->pegawai as $emp)
        <li>{{ $emp->nama }} ({{ $emp->code }})</li>
        @endforeach
    </ul>
    @else
    <em>Belum ada pegawai di grup ini.</em>
    @endif
</div>
@stop