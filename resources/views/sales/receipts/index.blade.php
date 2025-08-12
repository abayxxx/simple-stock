@extends('adminlte::page')
@section('title', 'Daftar Tanda Terima Penjualan')
@section('content_header')
<h1>Daftar Tanda Terima Penjualan</h1>
@stop
@section('content')
<div class="mb-3">
    <a href="{{ route('sales.receipts.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Tanda Terima</a>
</div>
<div class="card">
    <div class="card-header">
        <div class="row g-2 align-items-center">

            <!-- Date range -->
            <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                <input type="date" id="periode_awal" class="form-control" style="min-width:140px">
                <span class="text-nowrap">s/d</span>
                <input type="date" id="periode_akhir" class="form-control" style="min-width:140px">
            </div>

            <!-- Customer -->
            <div class="col-12 col-sm-6 col-md-auto">
                <select id="filter_customer" class="form-select w-100 form-control">
                    <option value="">Semua Customer</option>
                </select>
            </div>

            <!-- Collector -->
            <div class="col-12 col-sm-6 col-md-auto">
                <select id="filter_collector" class="form-select w-100 form-control">
                    <option value="">Semua Penagih</option>
                </select>
            </div>

        </div>
    </div>
    <div class="card-body table-responsive">
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
                    d.customer_id = $('#filter_customer').val();
                    d.collector_id = $('#filter_collector').val();
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
            loadFilterOptions();
            $('#receipts-table').DataTable().ajax.reload();
        });

        function loadFilterOptions() {
            const awal = $('#periode_awal').val();
            const akhir = $('#periode_akhir').val();
            // if (!awal || !akhir) {
            //     // Optional: clear dropdowns if date range incomplete
            //     return;
            // }

            $.get("{{ route('sales.receipts.filter-options') }}", {
                awal,
                akhir
            }, function(res) {
                // res: { customers: [{id,name}], locations: [{id,name}], sales_groups: [{id,nama}] }
                const $sup = $('#filter_customer').empty().append('<option value="">Semua Customer</option>');
                res.customers.forEach(o => $sup.append(`<option value="${o.id}">${o.name}</option>`));

                const $sg = $('#filter_collector').empty().append('<option value="">Semua Penagih</option>');
                res.collectors.forEach(o => $sg.append(`<option value="${o.id}">${o.nama}</option>`));

                // after refresh options, reload table with new filters
                $('#receipts-table').DataTable().ajax.reload();
            });
        }


        // reload table when any dropdown changes
        $('#filter_customer, #filter_kolektor').on('change', function() {
            $('#receipts-table').DataTable().ajax.reload();
        });

        // optional: first load (if you want them empty until dates picked, you can skip this)
        loadFilterOptions();
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css">
@endpush