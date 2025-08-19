@extends('adminlte::page')
@section('title', 'Kartu Stok')
@section('content_header')
<h1>Kartu Stok</h1>
@stop

@section('content')
<div class="card mb-4">
    <div class="card-body">
        <form id="filter-form" >
            <div class="row g-2 align-items-end">
                <div class="col-md-3 col-12 ">
                    <label class="form-label">Produk</label>
                    <select id="select-product" name="product_id" class="form-control">
                        <option value="">-- Pilih Produk --</option>

                    </select>
                </div>

                <div class="col-md-2 col-12 ">
                    <label class="form-label">Mulai</label>
                    <input type="date" id="periode_awal" name="periode_awal" class="form-control" value="{{ date('Y-m-01') }}">
                </div>
                <div class="col-md-2 col-12 ">
                    <label class="form-label">Sampai</label>
                    <input type="date" id="periode_akhir" name="periode_akhir" class="form-control" value="{{ date('Y-m-d') }}">
                </div>
                <div class="col-md-1 col-12 d-grid">
                    <button type="button" id="btn-refresh" class="btn btn-primary"> REFRESH</button>
                </div>
               <div class="ml-2 row g-2 align-items-end">
            <div class="mr-2">
                    <a href="#" class="btn btn-success" id="export-btn" target="_blank">
                        <i class="fa fa-file-excel"></i>
                        Export Excel
                    </a>
                </div>
            <div class="">
                    <a href="#" class="btn btn-danger" id="export-pdf-btn" target="_blank">
                        <i class="fa fa-file-pdf"></i>
                        Export PDF
                    </a>
                </div>
            </div>
        </div>
            </div>
        </form>

       
    </div>
</div>

<div class="mb-2">
    <div id="summary-bar" class="p-2 border bg-light rounded" style="font-size:1.15em">
        <b>TOTAL MASUK:</b> <span id="total-masuk">0</span> &nbsp; &nbsp;
        <b>TOTAL KELUAR:</b> <span id="total-keluar">0</span> &nbsp; &nbsp;
        <b>SISA:</b> <span id="saldo-akhir">0</span>
    </div>
</div>
<div class="card">
    <div class="card-body">
        <table class="table table-striped table-bordered" id="kartu-stok-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Tipe Stok</th>
                    <th>No Transaksi</th>
                    <th>Nama</th>
                    <th>Masuk</th>
                    <th>Keluar</th>
                    <th>Sisa</th>
                </tr>
            </thead>
            <tbody>
                <!-- Diisi via JS -->
            </tbody>
        </table>
    </div>
</div>
@stop

@push('js')
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
    function reloadKartuStok() {
        let params = {
            product_id: $('#select-product').val(),
            // lokasi_id: $('#select-branch').val(),
            periode_awal: $('#periode_awal').val(),
            periode_akhir: $('#periode_akhir').val(),
        };
        $.get("{{ route('stocks.cards.datatable') }}", params, function(res) {
            let rows = '';
            res.data.forEach(r => {
                rows += `<tr>
                <td>${r.tanggal}</td>
                <td>${r.tipe_stock}</td>
                <td>${r.catatan}</td>
                <td>${r.nama}</td>
                <td>${r.masuk}</td>
                <td>${r.keluar}</td>
                <td>${r.sisa}</td>
            </tr>`;
            });
            $('#kartu-stok-table tbody').html(rows);
            $('#total-masuk').text(res.total_masuk + ' ' + (res.satuan || ''));
            $('#total-keluar').text(res.total_keluar + ' ' + (res.satuan || ''));
            $('#saldo-akhir').text(res.saldo_akhir + ' ' + (res.satuan || ''));
        });
    }
    $('#btn-refresh').on('click', reloadKartuStok);
    $('#filter-form select, #filter-form input').on('change', reloadKartuStok);
    $(function() {
        reloadKartuStok();

        $('#select-product').select2({
            placeholder: '-- Pilih Produk --',
            minimumInputLength: 2,
            ajax: {
                url: '{{ url("admin/products/search") }}',
                dataType: 'json',
                delay: 250,
                data: function(params) {
                    return {
                        q: params.term // user typed text
                    };
                },
                processResults: function(data) {
                    // Expect: [{id:1, text:"001-Produk A", satuan_kecil:"pcs"}, ...]
                    return {
                        results: data.map(function(p) {
                            return {
                                id: p.id,
                                text: p.kode + ' - ' + p.nama,
                                satuan_kecil: p.satuan_kecil
                            }
                        })
                    };
                }
            }
        });

        function buildExportUrl() {
            let periodeAwal = $('#periode_awal').val();
            let periodeAkhir = $('#periode_akhir').val();
            let productId = $('#select-product').val();


            return "{{ route('stocks.cards.export') }}" + "?periode_awal=" + periodeAwal + "&periode_akhir=" + periodeAkhir + "&product_id=" + productId;
        }
        $('#export-btn').attr('href', buildExportUrl());
        $('#periode_awal, #periode_akhir, #select-product').on('input change', function() {
            $('#export-btn').attr('href', buildExportUrl());
        });

        function buildExportPdfUrl() {
            let periodeAwal = $('#periode_awal').val();
            let periodeAkhir = $('#periode_akhir').val();
            let productId = $('#select-product').val();

            return "{{ route('stocks.cards.exportPdf') }}" + "?periode_awal=" + periodeAwal + "&periode_akhir=" + periodeAkhir + "&product_id=" + productId;
        }
        $('#export-pdf-btn').attr('href', buildExportPdfUrl());
        $('#periode_awal, #periode_akhir, #select-product').on('change', function() {
            $('#export-pdf-btn').attr('href', buildExportPdfUrl());
        });
    });
</script>
@endpush

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/jquery.dataTables.min.css" />
<style>
    /* Match Select2 single select to Bootstrap 4/5 .form-control */
    .select2-container--default .select2-selection--single {
        height: 38px !important;
        /* Default Bootstrap 4/5 input height */
        padding: 6px 12px !important;
        font-size: 1rem !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important;
        /* For Bootstrap 4, use 0.375rem for Bootstrap 5 */
        display: flex;
        align-items: center;
        box-sizing: border-box;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 24px !important;
        padding-left: 0 !important;
    }

    .select2-selection__arrow {
        height: 36px !important;
        right: 6px;
        top: 1px;
    }
</style>
@endpush