@extends('adminlte::page')
@section('title', 'Daftar Tanda Terima Penjualan')
@section('content_header')
<h1>Daftar Tanda Terima Penjualan</h1>
@stop
@section('content')
<div class="mb-3">
    <a href="{{ route('receipts.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Tanda Terima</a>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center">
        <div>
            <input type="date" id="periode_awal" class="form-control d-inline-block" style="width:140px">
            s/d
            <input type="date" id="periode_akhir" class="form-control d-inline-block" style="width:140px">
        </div>
    </div>
    <div class="card-body">
        <table class="table table-bordered" id="receipts-table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
                    <th>Penagih</th>
                    <th>Total Faktur</th>
                    <th>Total Retur</th>
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
        $('#receipts-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sales.receipts.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                }
            },
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
                    data: 'customer',
                    name: 'customer.name'
                },
                {
                    data: 'collector',
                    name: 'collector.nama'
                },
                {
                    data: 'total_faktur',
                    name: 'total_faktur',
                    searchable: false,
                    orderable: false
                },
                {
                    data: 'total_retur',
                    name: 'total_retur',
                    searchable: false,
                    orderable: false,
                    className: 'text-end'
                },
                {
                    data: 'aksi',
                    name: 'aksi',
                    orderable: false,
                    searchable: false
                }
            ]
        });

        // Filter date on change
        $('#periode_awal, #periode_akhir').on('change', function() {
            console.log('Filter date changed');
            $('#receipts-table').DataTable().ajax.reload();
        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@endpush