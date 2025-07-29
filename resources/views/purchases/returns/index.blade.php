@extends('adminlte::page')
@section('title', 'Retur Pembelian')
@section('content_header')
<h1>Retur Pembelian</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('purchases.returns.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Retur Pembelian</a>
</div>
<div class="card">
    <div class="card-header d-flex align-items-center">
        <div>
            <input type="date" id="periode_awal" class="form-control d-inline-block" style="width:140px">
            s/d
            <input type="date" id="periode_akhir" class="form-control d-inline-block" style="width:140px">
        </div>
    </div>
    <div class="card-body p-2 table-responsive">
        <table class="table table-bordered " id="table-retur">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>No Retur</th>
                    <th>Supplier</th>
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
            ajax: {
                url: "{{ route('purchases.returns.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                }
            },
            columns: [{
                    data: 'tanggal',
                    name: 'tanggal'
                },
                {
                    data: 'kode',
                    name: 'kode'
                },
                {
                    data: 'supplier',
                    name: 'supplier'
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

        // Filter date on change
        $('#periode_awal, #periode_akhir').on('change', function() {
            console.log('Filter date changed');
            $('#table-retur').DataTable().ajax.reload();
        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush