@extends('adminlte::page')
@section('title', 'Faktur Pembelian Detail')

@section('content_header')
<h1>Faktur Pembelian - Detail</h1>
@stop

@section('content')
<div class="mb-2 mt-3">
    <form class="form-inline" id="filter-form">
        <input type="date" name="from" class="form-control mr-1" value="{{ request('from', date('Y-m-01')) }}">
        <input type="date" name="to" class="form-control mr-1" value="{{ request('to', date('Y-m-d')) }}">
        <div class="col-12 col-sm-6 col-md-auto">
                <select id="filter_supplier" class="form-select w-100 form-control" name="supplier_id">
                    <option value="">Semua Supplier</option>
                </select>
            </div>
        <button type="submit" class="btn btn-primary btn-sm">Filter</button>
        <a id="export-btn" href="#" class="btn btn-success btn-sm ml-1" target="_blank">
            <i class="fa fa-file-excel"></i> Export Excel
        </a>
    </form>
</div>
<div class="table-responsive">
    <table id="faktur-detail-table" class="table table-bordered table-sm" style="min-width: 1650px;">
        <thead>
            <tr>
                <th>NO.</th>
                <th>TANGGAL</th>
                <th>NAMA</th>
                <th>ALAMAT</th>
                <th>NAMA PRODUK</th>
                <th>QTY.</th>
                <th>SATUAN</th>
                <th>HARGA (KECIL)</th>
                <th>DISC. 1 (NILAI)</th>
                <th>DISC. 2 (NILAI)</th>
                <th>SUB TOTAL</th>
            </tr>
        </thead>
        <tfoot>
            <tr class="grand-total-footer">
                <td colspan="5" class="text-right font-weight-bold">GRAND TOTAL</td>
                <td id="grand-total-qty" class="text-right font-weight-bold"></td>
                <td></td>
                <td></td>
                <td id="grand-total-disc1" class="text-right font-weight-bold"></td>
                <td id="grand-total-disc2" class="text-right font-weight-bold"></td>
                <td id="grand-total-subtotal" class="text-right font-weight-bold"></td>
            </tr>
        </tfoot>
        <tbody></tbody>
    </table>
</div>
@endsection

@push('css')
<style>
    /* Warna sesuai desktop-mu */
    .group-header-green {
        background: #B7F5BF !important;
        font-weight: bold;
        color: #000;
    }

    .group-header-yellow {
        background: #FFFCC1 !important;
        font-weight: bold;
        color: #000;
    }

    .group-item-yellow {
        background: #FFFCC1 !important;
    }

    .subtotal-row {
        background: #efefef !important;
        font-weight: bold;
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
<script src="https://cdn.datatables.net/v/bs4/dt-1.13.6/r-2.5.0/datatables.min.js"></script>
<script>
    let colorToggle = false; // Untuk switch warna group (hijau/kuning) bergantian

    $(function() {
        let table = $('#faktur-detail-table').DataTable({
            processing: true,
            serverSide: true,
            paging: true,
            pageLength: 25,
            ordering: false, // biar urut dari backend
            searching: true,
            ajax: {
                url: '{{ route("purchases.detail.data") }}',
                data: function(d) {
                    d.from = $('[name="from"]').val();
                    d.to = $('[name="to"]').val();
                    d.supplier_id = $('#filter_supplier').val();
                }
            },
            columns: [{
                    data: 'faktur_no',
                    name: 'faktur_no'
                }, // group NO.
                {
                    data: 'tanggal',
                    name: 'tanggal'
                },
                {
                    data: 'supplier',
                    name: 'supplier',
                },
                {
                    data: 'alamat',
                    name: 'alamat'
                },
                {
                    data: 'product',
                    name: 'product'
                },
                {
                    data: 'qty',
                    name: 'qty',
                    className: 'text-right'
                },
                {
                    data: 'satuan',
                    name: 'satuan'
                },
                {
                    data: 'harga',
                    name: 'harga',
                    className: 'text-right'
                },
                {
                    data: 'disc_1',
                    name: 'disc_1',
                    className: 'text-right'
                },
                {
                    data: 'disc_2',
                    name: 'disc_2',
                    className: 'text-right'
                },
                {
                    data: 'sub_total',
                    name: 'sub_total',
                    className: 'text-right'
                },
            ],
            drawCallback: function(settings) {
                // Custom group header & subtotal inject
                let api = this.api();
                let rows = api.rows({
                    page: 'current'
                }).nodes();
                let data = api.rows({
                    page: 'current'
                }).data();

                let lastGroup = null;
                let subtotalQty = 0,
                    subtotalDisc1 = 0,
                    subtotalDisc2 = 0,
                    subtotalSubtotal = 0;
                let grandQty = 0,
                    grandDisc1 = 0,
                    grandDisc2 = 0,
                    grandSubtotal = 0;

                data.each(function(row, i) {
                    let group = row.faktur_no || '-';
                    let qty = Number(row.qty) || 0;
                    let disc1 = Number(row.disc_1.replace(/\./g, '').replace(',', '.')) || 0;
                    let disc2 = Number(row.disc_2.replace(/\./g, '').replace(',', '.')) || 0;
                    let subtotal = Number(row.sub_total.replace(/\./g, '').replace(',', '.')) || 0;

                    if (lastGroup !== group) {
                        // Set warna group header, toggle per faktur
                        let groupClass = 'group-header-green';
                        $(rows).eq(i).before(
                            `<tr class="${groupClass}">
                            <td colspan="11">
                                NO. : ${row.faktur_no}
                            </td>
                        </tr>`
                        );

                        // Reset subtotal
                        subtotalQty = 0;
                        subtotalDisc1 = 0;
                        subtotalDisc2 = 0;
                        subtotalSubtotal = 0;
                    }

                    // Tambahkan warna ke row item
                    $(rows).eq(i).addClass('group-item-yellow');

                    subtotalQty += qty;
                    subtotalDisc1 += disc1;
                    subtotalDisc2 += disc2;
                    subtotalSubtotal += subtotal;
                    grandQty += qty;
                    grandDisc1 += disc1;
                    grandDisc2 += disc2;
                    grandSubtotal += subtotal;

                    // If next group, render subtotal
                    let nextGroup = data[i + 1] ? data[i + 1].faktur_no : null;
                    if (group !== nextGroup) {
                        // Subtotal row
                        $(rows).eq(i).after(
                            `<tr class="subtotal-row">
                            <td colspan="5" class="text-right">SUBTOTAL</td>
                            <td class="text-right">${subtotalQty.toLocaleString('id-ID')}</td>
                            <td></td>
                            <td></td>
                            <td class="text-right">${subtotalDisc1.toLocaleString('id-ID')}</td>
                            <td class="text-right">${subtotalDisc2.toLocaleString('id-ID')}</td>
                            <td class="text-right">${subtotalSubtotal.toLocaleString('id-ID')}</td>
                        </tr>`
                        );
                    }
                    lastGroup = group;
                });

                // Update grand total di tfoot
                $('#grand-total-qty').text(grandQty.toLocaleString('id-ID'));
                $('#grand-total-disc1').text(grandDisc1.toLocaleString('id-ID'));
                $('#grand-total-disc2').text(grandDisc2.toLocaleString('id-ID'));
                $('#grand-total-subtotal').text(grandSubtotal.toLocaleString('id-ID'));
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
            let supplier_id = $('#filter_supplier').val();
            return "{{ route('purchases.detail.export') }}" + "?from=" + from + "&to=" + to + "&supplier_id=" + supplier_id;
        }
        $('#export-btn').attr('href', buildExportUrl());
        $('[name=from], [name=to], [name=supplier_id]').on('change', function() {
            $('#export-btn').attr('href', buildExportUrl());
        });

        function loadFilterOptions() {
            const awal = $('[name=from]').val();
            const akhir = $('[name=to]').val();
            // if (!awal || !akhir) {
            //     // Optional: clear dropdowns if date range incomplete
            //     return;
            // }

            $.get("{{ route('purchases.invoices.filter-options') }}", {
                awal,
                akhir
            }, function(res) {
                // res: { customers: [{id,name}], locations: [{id,name}], sales_groups: [{id,nama}] }
                const $sup = $('#filter_supplier').empty().append('<option value="">Semua Supplier</option>');
                res.suppliers.forEach(o => $sup.append(`<option value="${o.id}">${o.name}</option>`));

                // after refresh options, reload table with new filters
                table.ajax.reload();
            });
        }

         // optional: first load (if you want them empty until dates picked, you can skip this)
        loadFilterOptions();
    });
</script>
@endpush