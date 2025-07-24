@extends('adminlte::page')
@section('title', 'Retur Penjualan')
@section('content_header')
<h1>Retur Penjualan</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('returns.create') }}" class="btn btn-success"><i class="fa fa-plus"></i> Tambah Retur</a>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-bordered table-striped" id="table-retur">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No Retur</th>
                    <th>Customer</th>
                    <th>Sales Group</th>
                    <th>Grand Total</th>
                    <th>Total Bayar</th>
                    <th>Sisa Tagihan</th>
                    <th>Aksi</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    $(function() {
        $('#table-retur').DataTable({
            processing: true,
            serverSide: true,
            ajax: '{{ route("sales.returns.datatable") }}',
            columns: [{
                    data: 'tanggal',
                    name: 'tanggal'
                },
                {
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'customer',
                    name: 'customer'
                },
                {
                    data: 'sales_group',
                    name: 'sales_group'
                },
                {
                    data: 'grand_total',
                    name: 'grand_total',
                    className: 'text-end'
                },
                {
                    data: 'total_bayar',
                    name: 'total_bayar',
                    className: 'text-end'
                },
                {
                    data: 'sisa_tagihan',
                    name: 'sisa_tagihan',
                    className: 'text-end'
                },
                {
                    data: 'aksi',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });
</script>
@endpush