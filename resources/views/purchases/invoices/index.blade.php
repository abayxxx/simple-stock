@extends('adminlte::page')

@section('title', 'Faktur Pembelian')

@section('content_header')
<h1>Daftar Faktur Pembelian</h1>
@stop

@section('content')
@if(session('success'))
<div class="alert alert-success">{{ session('success') }}</div>
@endif
<div class="mb-3">
    <a href="{{ route('purchases.invoices.create') }}" class="btn btn-primary"><i class="fa fa-plus"></i> Tambah Pembelian</a>
</div>
<div class="card shadow">
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
        <table id="tabel-faktur" class="table table-sm table-bordered">
            <thead class="bg-light text-center align-middle">
                <tr>
                    <th>TANGGAL</th>
                    <th>NO.</th>
                    <th>SUPPLIER</th>
                    <th>GRAND TOTAL</th>
                    <th>RETUR</th>
                    <th>PEMBAYARAN</th>
                    <th>SISA</th>
                    <th>TGL. INPUT</th>
                    <th>TGL. PEMBAYARAN</th>
                    <th>AKSI</th>
                </tr>
            </thead>
            <tfoot class="bg-light">
                <tr>
                    <th colspan="3" class="text-end">TOTAL</th>
                    <th id="footer-grandtotal" class="text-end"></th>
                    <th id="footer-retur" class="text-end"></th>
                    <th id="footer-bayar" class="text-end"></th>
                    <th id="footer-sisa" class="text-end"></th>
                    <th></th>
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
                url: "{{ route('purchases.invoices.datatable') }}",
                data: function(d) {
                    d.periode_awal = $('#periode_awal').val();
                    d.periode_akhir = $('#periode_akhir').val();
                    d.supplier_id = $('#filter_supplier').val();
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
                    data: 'supplier',
                    className: 'align-middle',
                    width: '17%'
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
                    data: 'tgl_pembayaran',
                    className: 'text-center align-middle',
                    width: '10%'
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
                let colGrand = 3,
                    colRetur = 4,
                    colBayar = 5,
                    colSisa = 6;

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
            loadFilterOptions();
            table.ajax.reload();

        });

        function loadFilterOptions() {
            const awal = $('#periode_awal').val();
            const akhir = $('#periode_akhir').val();
            // if (!awal || !akhir) {
            //     // Optional: clear dropdowns if date range incomplete
            //     return;
            // }

            $.get("{{ route('purchases.invoices.filter-options') }}", {
                awal,
                akhir
            }, function(res) {
                // res: { suppliers: [{id,name}] }
                const $sup = $('#filter_supplier').empty().append('<option value="">Semua Supplier</option>');
                res.suppliers.forEach(o => $sup.append(`<option value="${o.id}">${o.name}</option>`));

                // after refresh options, reload table with new filters
                table.ajax.reload();
            });
        }

        // reload table when any dropdown changes
        $('#filter_supplier').on('change', function() {
            table.ajax.reload();
        });

        // optional: first load (if you want them empty until dates picked, you can skip this)
        loadFilterOptions();
    });
</script>
@endpush

@push('css')
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
@endpush