{{-- resources/views/laporan/hutang/index.blade.php --}}
@extends('adminlte::page')

@section('title', 'Laporan Hutang')

@section('content_header')
    <h1>Laporan Hutang</h1>
@stop

@section('content')
<div class="card">
    <div class="card-header">
        <form id="filter-form" class="form-inline">
            <label for="tanggal" class="mr-2">Sampai Tanggal:</label>
            <input type="date" name="tanggal" id="tanggal" value="{{ $date }}" class="form-control mr-2">
            <button type="submit" class="btn btn-primary btn-sm">Tampilkan</button>
        </form>
    </div>
    <div class="card-body p-0">
        <table id="hutang-table" class="table table-bordered table-hover table-sm mb-0">
            <thead>
                <tr>
                    <th>KODE</th>
                    <th>NAMA</th>
                    <th>KATEGORI</th>
                    <th>DEBET</th>
                    <th>KREDIT</th>
                    <th>SISA</th>
                </tr>
            </thead>
            <tfoot>
                <tr>
                    <th colspan="3" class="text-right">Total</th>
                    <th id="total-debet" class="text-right"></th>
                    <th id="total-kredit" class="text-right"></th>
                    <th id="total-sisa" class="text-right"></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
@stop

@section('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
$(function() {
    function loadTable(tanggal = '') {
        $('#hutang-table').DataTable({
            processing: true,
            serverSide: true,
            destroy: true,
            ajax: {
                url: "{{ route('finances.debt.index') }}",
                data: {tanggal: tanggal}
            },
            columns: [
                { data: 'kode', name: 'kode' },
                { data: 'nama', name: 'nama' },
                { data: 'kategori', name: 'kategori' },
                { data: 'debet', name: 'debet', className: 'text-right' },
                { data: 'kredit', name: 'kredit', className: 'text-right' },
                { data: 'sisa', name: 'sisa', className: 'text-right' },
            ],
            rowCallback: function(row, data) {
                 if (parseFloat(data.sisa_raw) > 0) {
                    $(row).css('background-color', '#fffde4');
                } else {
                    $(row).css('background-color', '#e5fbe3');
                }
            },
            drawCallback: function(settings) {
                var api = this.api();
                var totalDebet = 0, totalKredit = 0, totalSisa = 0;
                api.rows({ page: 'current' }).data().each(function(d){
                    totalDebet += parseFloat((d.debet+'').replace(/\./g, '').replace(',', '.')) || 0;
                    totalKredit += parseFloat((d.kredit+'').replace(/\./g, '').replace(',', '.')) || 0;
                    totalSisa += parseFloat((d.sisa+'').replace(/\./g, '').replace(',', '.')) || 0;
                });
                $('#total-debet').html(totalDebet.toLocaleString('id-ID', {minimumFractionDigits:2}));
                $('#total-kredit').html(totalKredit.toLocaleString('id-ID', {minimumFractionDigits:2}));
                $('#total-sisa').html(totalSisa.toLocaleString('id-ID', {minimumFractionDigits:2}));
            }
        });
    }

    loadTable($('#tanggal').val());

    $('#filter-form').on('submit', function(e) {
        e.preventDefault();
        loadTable($('#tanggal').val());
    });
});
</script>
@stop


@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush
