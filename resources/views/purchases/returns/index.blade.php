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
    <div class="card-header">
        <div class="row g-2 align-items-center">

            <!-- Date range -->
            <div class="col-12 col-md-auto d-flex align-items-center gap-2">
                <input type="date" id="periode_awal" class="form-control" style="min-width:140px">
                <span class="text-nowrap">s/d</span>
                <input type="date" id="periode_akhir" class="form-control" style="min-width:140px">
            </div>

            <!-- Supplier -->
            <div class="col-12 col-sm-6 col-md-auto">
                <select id="filter_supplier" class="form-select w-100 form-control">
                    <option value="">Semua Supplier</option>
                </select>
            </div>
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
                    d.supplier_id = $('#filter_supplier').val();
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
            loadFilterOptions();
            $('#table-retur').DataTable().ajax.reload();
        });

        function loadFilterOptions() {
            const awal = $('#periode_awal').val();
            const akhir = $('#periode_akhir').val();
            // if (!awal || !akhir) {
            //     // Optional: clear dropdowns if date range incomplete
            //     return;
            // }

            $.get("{{ route('purchases.returns.filter-options') }}", {
                awal,
                akhir
            }, function(res) {
                // res: { suppliers: [{id,name}] }
                const $sup = $('#filter_supplier').empty().append('<option value="">Semua Supplier</option>');
                res.suppliers.forEach(o => $sup.append(`<option value="${o.id}">${o.name}</option>`));

                // after refresh options, reload table with new filters
                $('#table-retur').DataTable().ajax.reload();
            });
        }

        // reload table when any dropdown changes
        $('#filter_supplier').on('change', function() {
            $('#table-retur').DataTable().ajax.reload();
        });

        // optional: first load (if you want them empty until dates picked, you can skip this)
        loadFilterOptions();
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush