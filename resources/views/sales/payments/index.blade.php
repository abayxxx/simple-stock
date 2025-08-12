@extends('adminlte::page')
@section('title', 'Pembayaran Penjualan')

@section('content_header')
<h1>Pembayaran Penjualan</h1>
@stop

@section('content')
<div class="mb-3">
    <a href="{{ route('sales.payments.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Pembayaran</a>
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

        </div>
    </div>
    <div class="card-body table-responsive">
        <table class="table table-bordered" id="datatable">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Kode</th>
                    <th>Tanggal</th>
                    <th>Customer</th>
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
            ajax: {
                url: "{{ route('sales.payments.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                    d.customer_id = $('#filter_customer').val();
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
                    name: 'customer'
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

        // Filter date on change
        $('#periode_awal, #periode_akhir').on('change', function() {
            loadFilterOptions();
            $('#datatable').DataTable().ajax.reload();
        });

        function loadFilterOptions() {
            const awal = $('#periode_awal').val();
            const akhir = $('#periode_akhir').val();
            // if (!awal || !akhir) {
            //     // Optional: clear dropdowns if date range incomplete
            //     return;
            // }

            $.get("{{ route('sales.payments.filter-options') }}", {
                awal,
                akhir
            }, function(res) {
                // res: { customers: [{id,name}], locations: [{id,name}], sales_groups: [{id,nama}] }
                const $sup = $('#filter_customer').empty().append('<option value="">Semua Supplier</option>');
                res.customers.forEach(o => $sup.append(`<option value="${o.id}">${o.name}</option>`));

                const $sg = $('#filter_sg').empty().append('<option value="">Semua Sales Group</option>');
                res.sales_groups.forEach(o => $sg.append(`<option value="${o.id}">${o.nama}</option>`));

                // after refresh options, reload table with new filters
                $('#datatable').DataTable().ajax.reload();
            });
        }


        // reload table when any dropdown changes
        $('#filter_customer, #filter_sg').on('change', function() {
            $('#datatable').DataTable().ajax.reload();
        });

        // optional: first load (if you want them empty until dates picked, you can skip this)
        loadFilterOptions();
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush