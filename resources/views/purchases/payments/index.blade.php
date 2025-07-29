@extends('adminlte::page')
@section('title', 'Pembayaran Pembelian')

@section('content_header')
<h1>Pembayaran Pembelian</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('purchases.payments.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Pembayaran</a>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-bordered" id="datatable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Supplier</th>
                    <th>Jumlah Nota</th>
                    <th>Total Bayar</th>
                    <th>User</th>
                    <th></th>
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
        $('#datatable').DataTable({
            processing: true,
            serverSide: true,
            ajax: "{{ route('purchases.payments.datatable') }}",
            columns: [{
                    data: 'DT_RowIndex',
                    name: 'DT_RowIndex',
                    orderable: false,
                    searchable: false
                },
                {
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'tanggal',
                    name: 'tanggal'
                },
                {
                    data: 'supplier',
                    name: 'supplier'
                },
                {
                    data: 'jumlah_nota',
                    name: 'jumlah_nota'
                },
                {
                    data: 'total_bayar',
                    name: 'total_bayar',
                    className: 'text-right'
                },
                {
                    data: 'user.name',
                    name: 'user.name',
                    defaultContent: '-'
                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false
                }
            ]
        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush