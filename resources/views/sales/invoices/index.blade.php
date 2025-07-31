@extends('adminlte::page')

@section('title', 'Faktur Penjualan')

@section('content_header')
<h1>Daftar Faktur Penjualan</h1>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="mb-3">
    <a href="{{ route('sales.invoices.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Tanda Terima</a>
</div>
<div class="card shadow">
    <div class="card-header d-flex align-items-center">
        <div>
            <input type="date" id="periode_awal" class="form-control d-inline-block" style="width:140px">
            s/d
            <input type="date" id="periode_akhir" class="form-control d-inline-block" style="width:140px">
        </div>
    </div>
    <div class="card-body p-2 table-responsive">
        <table id="tabel-faktur" class="table table-sm table-bordered">
            <thead class="bg-light text-center align-middle">
                <tr>
                    <th>TANGGAL</th>
                    <th>NO.</th>
                    <th>NAMA</th>
                    <th>JATUH TEMPO</th>
                    <th>NAMA SG</th>
                    <th>GRAND TOTAL</th>
                    <th>RETUR</th>
                    <th>PEMBAYARAN</th>
                    <th>SISA</th>
                    <th>TGL. INPUT</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="5" class="text-end">TOTAL</th>
                    <th id="footer-grandtotal" class="text-end"></th>
                    <th id="footer-retur" class="text-end"></th>
                    <th id="footer-bayar" class="text-end"></th>
                    <th id="footer-sisa" class="text-end"></th>
                    <th></th>
                    <th></th>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>

<script>
    $(function() {
        let table = $('#tabel-faktur').DataTable({
            processing: true,
            serverSide: true,
            ajax: {
                url: "{{ route('sales.invoices.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                }
            },
            paging: true,
            pageLength: 25,
            lengthMenu: [
                [10, 25, 50, 100, -1],
                [10, 25, 50, 100, "Semua"]
            ],
            searching: true,
            ordering: true,
            order: [
                [0, 'desc']
            ],
            columns: [{
                    data: 'tanggal',
                    className: 'text-center align-middle',
                    width: '8%'
                },
                {
                    data: 'kode',
                    className: 'text-center align-middle',
                    width: '11%'
                },
                {
                    data: 'customer',
                    className: 'align-middle',
                    width: '17%'
                },
                {
                    data: 'jatuh_tempo',
                    className: 'text-center align-middle',
                    width: '9%'
                },
                {
                    data: 'sales_group',
                    className: 'text-center align-middle',
                    width: '8%'
                },
                {
                    data: 'grand_total',
                    className: 'text-end align-middle fw-bold',
                    width: '11%'
                },
                {
                    data: 'total_retur',
                    className: 'text-end align-middle',
                    width: '8%'
                },
                {
                    data: 'total_bayar',
                    className: 'text-end align-middle',
                    width: '10%'
                },
                {
                    data: 'sisa_tagihan',
                    className: 'text-end align-middle',
                    width: '10%'
                },
                {
                    data: 'created_at',
                    className: 'text-center align-middle',
                    width: '8%'
                },
                {
                    data: 'aksi',
                    orderable: false,
                    searchable: false,
                    className: 'text-center',
                    width: '110px'
                }
            ],
            stripeClasses: ['bg-white', 'table-warning'],
            drawCallback: function(settings) {
                // Footer summary total
                let api = this.api();
                let colGrand = 5,
                    colRetur = 6,
                    colBayar = 7,
                    colSisa = 8;

                function intVal(i) {
                    if (typeof i === 'string') {
                        // Hapus "Rp ", titik ribuan, ubah koma jadi titik, baru parseFloat
                        return parseFloat(
                            i.replace(/Rp\s?/g, '').replace(/\./g, '').replace(',', '.')
                        ) || 0;
                    }
                    if (typeof i === 'number') return i;
                    return 0;
                }

                let sumCol = function(idx) {
                    let total = api.column(idx, {
                            search: 'applied'
                        }).data()
                        .reduce(function(a, b) {
                            return intVal(a) + intVal(b);
                        }, 0);

                    // Format hasil sum ke Rupiah
                    return total.toLocaleString('id-ID', {
                        style: 'currency',
                        currency: 'IDR',
                        minimumFractionDigits: 2,
                        maximumFractionDigits: 2
                    });
                };


                $('#footer-grandtotal').text(sumCol(colGrand));
                $('#footer-retur').text(sumCol(colRetur));
                $('#footer-bayar').text(sumCol(colBayar));
                $('#footer-sisa').text(sumCol(colSisa));
            },
            language: {
                "zeroRecords": "Tidak ada data",
                "processing": "Memuat...",
                "lengthMenu": "Tampil _MENU_",
                "search": "",
                "searchPlaceholder": "Cari faktur...",
                "paginate": {
                    "previous": "‹",
                    "next": "›"
                },

            },
            dom: '<"row"<"col-auto"l><"col"f>>rt<"row"<"col"i><"col-auto"p>>'
        });

        // Filter date on change
        $('#periode_awal, #periode_akhir').on('change', function() {
            table.ajax.reload();

        });
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush