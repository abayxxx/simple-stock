@extends('adminlte::page')
@section('title', 'Daftar Stok')

@section('content_header')
<h1>DAFTAR STOK</h1>
@stop

@section('content')
<div class="card">
    <div class="card-body">
        <form class=" mb-3" id="filter-form">
       <div class="row g-2 align-items-end">
    <div class="col-md-3 col-12">
        <label class="form-label">Periode</label>
        <input type="date" name="periode_awal" id="periode_awal" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-3 col-12">
        <label class="form-label">Sampai</label>
        <input type="date" name="periode_akhir" id="periode_akhir" class="form-control" value="{{ date('Y-m-d') }}">
    </div>
    <div class="col-md-1 col-12 d-grid">
        <button type="submit" class="btn btn-primary">Terapkan</button>
    </div>
    <div class="col-md-3 col-12 d-grid">
        <a href="#" class="btn btn-success" id="export-btn" target="_blank">
            <i class="fa fa-file-excel"></i>
            Export Excel
        </a>
    </div>
</div>
        </form>
        <div class="table-responsive">
            <table class="table table-bordered table-striped" id="stocks-table">
                <thead>
                    <tr>
                        <th>NAMA</th>
                        <th>HARGA JUAL</th>
                        <th>AKHIR</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>
@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    $(function() {
        let table = $('#stocks-table').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('stocks.lists.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('[name="periode_awal"]').val();
                    d.periode_akhir = $('[name="periode_akhir"]').val();
                }
            },
            columns: [{
                    data: 'nama',
                    name: 'nama'
                },
                {
                    data: 'harga_umum',
                    name: 'harga_umum',
                    className: 'text-end'
                },
                {
                    data: 'akhir',
                    name: 'akhir',
                    className: 'text-end'
                },
            ],
            search: {
                regex: true
            }
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault();
            table.ajax.reload();
        });

        function buildExportUrl() {
            let periodeAwal = $('#periode_awal').val();
            let periodeAkhir = $('#periode_akhir').val();

            return "{{ route('stocks.lists.export') }}" + "?periode_awal=" + periodeAwal + "&periode_akhir=" + periodeAkhir;
        }
        $('#export-btn').attr('href', buildExportUrl());
        $('#periode_awal, #periode_akhir').on('change', function() {
            $('#export-btn').attr('href', buildExportUrl());
        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush