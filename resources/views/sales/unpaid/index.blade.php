@extends('adminlte::page')
@section('title', 'Faktur Penjualan Belum Lunas per Customer')

@section('content_header')
<h1>Faktur Penjualan - Belum Lunas per Customer</h1>
@stop

@section('content')
<div class="mb-2 mt-3">
    <form class="form-inline" id="filter-form">
        <input type="date" name="from" class="form-control mr-1" value="{{ request('from', date('Y-m-01')) }}">
        <input type="date" name="to" class="form-control mr-1" value="{{ request('to', date('Y-m-d')) }}">
        <input type="text" name="customer" class="form-control mr-1" placeholder="Cari Customer" value="{{ request('customer') }}">
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a id="export-btn" href="#" class="btn btn-success btn-sm ml-1" target="_blank">
            <i class="fa fa-file-excel"></i> Export Excel
        </a>
    </form>
</div>
<div class="table-responsive">
    <table id="unpaid-invoice-table" class="table table-bordered table-sm" style="min-width: 1600px;">
        <thead>
            <tr>
                <th>NO.</th>
                <th>TANGGAL</th>
                <th>NAMA</th>
                <th>JATUH TEMPO</th>
                <th>NAMA GROUP</th>
                <th>GRAND TOTAL</th>
                <th>PEMBAYARAN</th>
                <th>SISA TAGIHAN</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="grand-total-footer">
                <td colspan="5" class="text-right font-weight-bold">GRAND TOTAL</td>
                <td id="grand-total-gt" class="text-right font-weight-bold"></td>
                <td id="grand-total-bayar" class="text-right font-weight-bold"></td>
                <td id="grand-total-sisa" class="text-right font-weight-bold"></td>
            </tr>
        </tfoot>
        <tbody></tbody>
    </table>
</div>
@endsection

@push('css')
<style>
    .group-header-green {
        background: #B7F5BF !important;
        font-weight: bold;
        color: #000;
    }

    .group-item-yellow {
        background: #FFFCC1 !important;
    }

    .grand-total-footer {
        background: #f8f8f8 !important;
        font-weight: bold;
        font-size: 1.07em;
    }

    .table th,
    .table td {
        font-size: 13px;
        vertical-align: middle;
    }

    .table-sm th,
    .table-sm td {
        padding: .3rem;
    }
</style>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush

@push('js')
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.6/datatables.min.js"></script>
<script>
    $(function() {
        let table = $('#unpaid-invoice-table').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            pageLength: 25,
            ordering: false,
            searching: true,
            ajax: {
                url: '{{ route("sales.unpaid.data") }}',
                data: function(d) {
                    d.from = $('[name="from"]').val();
                    d.to = $('[name="to"]').val();
                    d.customer = $('[name="customer"]').val();
                }
            },
            columns: [{
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
                    data: 'jatuh_tempo',
                    name: 'jatuh_tempo'
                },
                {
                    data: 'nama_group',
                    name: 'nama_group'
                },
                {
                    data: 'grand_total',
                    name: 'grand_total',
                    className: 'text-right'
                },
                {
                    data: 'total_bayar',
                    name: 'total_bayar',
                    className: 'text-right'
                },
                {
                    data: 'sisa_tagihan',
                    name: 'sisa_tagihan',
                    className: 'text-right'
                },
            ],
            drawCallback: function(settings) {
                // Grand total di tfoot
                let api = this.api();
                let data = api.rows({
                    page: 'current'
                }).data();
                let grand_gt = 0,
                    grand_bayar = 0,
                    grand_sisa = 0;
                data.each(function(row) {
                    grand_gt += Number(row.grand_total.replace(/\./g, '').replace(',', '.')) || 0;
                    grand_bayar += Number(row.total_bayar.replace(/\./g, '').replace(',', '.')) || 0;
                    grand_sisa += Number(row.sisa_tagihan.replace(/\./g, '').replace(',', '.')) || 0;
                });
                $('#grand-total-gt').text(grand_gt.toLocaleString('id-ID'));
                $('#grand-total-bayar').text(grand_bayar.toLocaleString('id-ID'));
                $('#grand-total-sisa').text(grand_sisa.toLocaleString('id-ID'));
            }
        });

        $('#filter-form').on('submit', function(e) {
            e.preventDefault();
            table.ajax.reload();
        });

        // Excel export
        function buildExportUrl() {
            let from = $('[name=from]').val();
            let to = $('[name=to]').val();
            let customer = $('[name=customer]').val();
            return "{{ route('sales.unpaid.export') }}" + "?from=" + from + "&to=" + to + "&customer=" + customer;
        }
        $('#export-btn').attr('href', buildExportUrl());
        $('[name=from], [name=to], [name=customer]').on('change keyup', function() {
            $('#export-btn').attr('href', buildExportUrl());
        });
    });
</script>
@endpush