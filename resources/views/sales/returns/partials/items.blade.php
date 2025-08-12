@php
$existingItems = old('items', isset($return) ? $return->items->toArray() : []);
@endphp

<div id="add-item-form" class="card p-3 mb-4 border shadow-sm">
    <div class="row">
        <div class="col-md-3 mb-2">
            <div class="d-flex ">
                <!-- ... -->
                <label>Produk</label>
                <button type="button" class="btn btn-link p-0 ms-1 ml-1" id="btn-history" style="font-size:1.15em;">
                    <i class="fa fa-info-circle text-info"></i>
                </button>
                <!-- ... -->

            </div>
             <select id="add-product_id" class="form-control select-product ">
                <option value="">-- Pilih Produk --</option>
    <!-- Options will be loaded dynamically by Select2 -->
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label>No Seri</label>
            <select id="add-no_seri" class="form-control select-no-seri">
                <option value="">-- Pilih No Seri --</option>
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label>Expired</label>
            <select id="add-tanggal_expired" class="form-control select-tanggal-expired">
                <option value="">-- Pilih Expired --</option>
            </select>
        </div>
    </div>
    <div class="row">
        <div class="col-md-2 mb-2">
            <label>Qty</label>
            <div class="input-group">
                <input id="add-qty" type="number" min="1" class="form-control qty-input">
                <span class="input-group-text satuan-box">Satuan</span>
            </div>
        </div>
        <input id="add-satuan" type="hidden">
        <div class="col-md-2 mb-2">
            <label>Harga</label>
            <input id="add-harga_satuan" type="number" step="0.01" class="form-control harga-input">
        </div>
        <div class="col-md-2 mb-2">
            <label>Sisa Stok</label>
            <input id="add-sisa_stok" class="form-control sisa-stok-input bg-success bg-opacity-25 fw-bold" readonly>
        </div>
        <div class="col-md-3 mb-2">
            <label>Subtotal Sebelum Diskon</label>
            <input id="add-sub_total_sblm_disc" type="number"
                class="form-control sub-total-sblm-disc bg-success bg-opacity-25" readonly>
        </div>
    </div>
    <div class=row>
        <div class="col-md-3 mb-2">
            <label>Diskon (%)</label>
            <div class="d-flex gap-1">
                @for($i=1;$i<=3;$i++)
                    <input id="add-diskon_{{$i}}_persen" type="number" step="0.01"
                    class="form-control diskon-persentase-input" placeholder="D{{ $i }}">
                    @endfor
            </div>
        </div>
        <div class="col-md-3 mb-2">
            <label>Diskon Harga Satuan</label>
            <div class="d-flex gap-1">
                @for($i=1;$i<=3;$i++)
                    <input id="add-diskon_{{$i}}_rupiah" type="number" step="0.01"
                    class="form-control diskon-rupiah-input" placeholder="Rp D{{ $i }}">
                    @endfor
            </div>
        </div>
        <div class="col-md-2 mb-2">
            <label>Total Diskon Item</label>
            <input id="add-total_diskon_item" type="number"
                class="form-control total-diskon-item-input bg-success bg-opacity-25" readonly>
        </div>
        <div class="col-md-2 mb-2">
            <label>Subtotal Sebelum PPN</label>
            <input id="add-sub_total_sebelum_ppn" type="number"
                class="form-control sub-total-sebelum-ppn-input bg-success bg-opacity-25" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-md-3 mb-2">
            <label>PPN (%)</label>
            <input id="add-ppn_persen" type="number" step="0.01" class="form-control ppn-persen-input">
        </div>
        <div class="col-md-3 mb-2">
            <label>Subtotal Setelah PPN</label>
            <input id="add-sub_total_setelah_disc" type="number"
                class="form-control sub-total-setelah-disc-input bg-success bg-opacity-25" readonly>
        </div>
    </div>
    <div class="row">
        <div class="col-md-10 mb-2">
            <label>Catatan</label>
            <textarea id="add-catatan" class="form-control"></textarea>
        </div>
        <div class="col-md-2 d-flex align-items-end mb-2">
            <button type="button" class="btn btn-success w-100" id="btn-add-item"><i class="fa fa-plus"></i> Tambah Barang</button>
        </div>
    </div>
</div>

<div style="overflow-x:auto;">
    <table class="table table-bordered" id="review-items-table" style="min-width:1800px">
        <thead>
            <tr>
                <th>Produk</th>
                <th>No Seri</th>
                <th>Expired</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga</th>
                <th>Sisa Stok</th>
                <th>Subtotal Sblm Diskon</th>
                <th>D1 (%)</th>
                <th>D1 (Rp)</th>
                <th>D2 (%)</th>
                <th>D2 (Rp)</th>
                <th>D3 (%)</th>
                <th>D3 (Rp)</th>
                <th>Total Diskon</th>
                <th>Subtotal Sblm PPN</th>
                <th>PPN (%)</th>
                <th>Subtotal Stlh PPN</th>
                <th>Catatan</th>
                <th>Hapus</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div id="hidden-inputs-container"></div>

@include('sales.returns.partials.modal-history')


@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
    $(function() {

        let itemIndex = 0;
        let products = @json(collect($products)->keyBy('id'));


        // -- Restore existing items for edit/validation
        let existingItems = @json($existingItems);
        if (existingItems.length) {
            existingItems.forEach((item, i) => {
                $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
                $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
                itemIndex++;
            });
        }

        function renderReviewRow(item, idx) {
            let produk = products[item.product_id] ? (products[item.product_id].kode + ' - ' + products[item.product_id].nama) : '';
            return `<tr data-index="${idx}">
        <td style="white-space:nowrap">${produk}</td>
        <td>${item.no_seri||''}</td>
        <td>${item.tanggal_expired||''}</td>
        <td>${item.qty||''}</td>
        <td>${(item.satuan||'').toUpperCase()}</td>
        <td>${item.harga_satuan||''}</td>
        <td>${item.sisa_stok||''}</td>
        <td>${item.sub_total_sblm_disc||''}</td>
        <td>${item.diskon_1_persen||''}</td>
        <td>${item.diskon_1_rupiah||''}</td>
        <td>${item.diskon_2_persen||''}</td>
        <td>${item.diskon_2_rupiah||''}</td>
        <td>${item.diskon_3_persen||''}</td>
        <td>${item.diskon_3_rupiah||''}</td>
        <td>${item.total_diskon_item||''}</td>
        <td>${item.sub_total_sebelum_ppn||''}</td>
        <td>${item.ppn_persen||''}</td>
        <td>${item.sub_total_setelah_disc||''}</td>
        <td>${item.catatan||''}</td>
        <td><button type="button" class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i> Hapus</button></td>
    </tr>`;
        }

        function renderHiddenInputs(item, idx) {
            let fields = [
                'product_id', 'no_seri', 'tanggal_expired',
                'qty', 'satuan', 'harga_satuan', 'sisa_stok',
                'sub_total_sblm_disc', 'diskon_1_persen', 'diskon_1_rupiah',
                'diskon_2_persen', 'diskon_2_rupiah', 'diskon_3_persen', 'diskon_3_rupiah',
                'total_diskon_item', 'sub_total_sebelum_ppn', 'ppn_persen', 'sub_total_setelah_disc', 'catatan'
            ];
            let html = '';
            fields.forEach(key => {
                html += `<input type="hidden" name="items[${idx}][${key}]" value="${item[key]||''}">`;
            });
            return `<div class="item-hidden" data-index="${idx}">${html}</div>`;
        }

        function initProductSelect(invoiceId = null) {
            $('#add-product_id').select2({
                placeholder: '-- Pilih Produk --',
                minimumInputLength: 0,
                ajax: {
                    url: function() {
                        // URL depends on selected invoice
                        if(invoiceId){
                            return `{{ url('admin/sales/returns/invoice-products-options') }}/${invoiceId}`;
                        }
                        return "{{ url('admin/sales/returns/invoice-products-options') }}"; // fallback, returns nothing
                    },
                    dataType: 'json',
                    delay: 250,
                    data: function(params) {
                        return {
                            q: params.term // search term
                        };
                    },
                    processResults: function(data) {
                        // Server should return: {products: [{id, text, satuan}]}
                        return {
                            results: (data.products || []).map(function(prod) {
                                return {
                                    id: prod.id,
                                    text: prod.text,
                                    satuan_kecil: prod.satuan || 'Satuan'
                                };
                            })
                        };
                    },
                    cache: true
                }
            });
        }

        // First time, initialize with no invoice selected
        initProductSelect($('#select-sales-invoice').val() || null);
        

        // Faktur change: update products, clear items table
        $('#select-sales-invoice').on('change', function() {
            let invoiceId = $(this).val();
            $('#review-items-table tbody').empty();
            $('#hidden-inputs-container').empty();
            itemIndex = 0;

               // Reset and reinitialize Select2 for products
            $('#add-product_id').val(null).trigger('change');
            $('#add-product_id').off('select2:select'); // Remove previous handlers if any
            $('#add-product_id').select2('destroy');    // Destroy old Select2
            initProductSelect(invoiceId); 

            $('#add-item-form input, #add-item-form select, #add-item-form textarea').val('');
            $('#add-satuan').val('');
            $('.satuan-box').text('Satuan');
            updateSummary();
        });

        // Product change: update batch/seri/expired
        $('#add-product_id').on('change', function() {
            let productId = $(this).val();
            let invoiceId = $('#select-sales-invoice').val();
            if (!productId || !invoiceId) {
                $('#add-no_seri').html('<option value="">-- Pilih No Seri --</option>');
                $('#add-tanggal_expired').html('<option value="">-- Pilih Expired --</option>');
                return;
            }
            $.get("/admin/sales/returns/invoice-product-options/" + invoiceId + "/" + productId, function(res) {
                let noSeriOpts = '<option value="">-- Pilih No Seri --</option>';
                let tglExpOpts = '<option value="">-- Pilih Expired --</option>';
                let uniqueNoSeri = [...new Set(res.batches.map(b => b.no_seri))];
                let uniqueExp = [...new Set(res.batches.map(b => b.tanggal_expired))];
                uniqueNoSeri.forEach(n => {
                    noSeriOpts += `<option value="${n}">${n}</option>`;
                });
                uniqueExp.forEach(t => {
                    tglExpOpts += `<option value="${t}">${t}</option>`;
                });
                $('#add-no_seri').html(noSeriOpts);
                $('#add-tanggal_expired').html(tglExpOpts);
            });
            // Update satuan
            let satuan = $('#add-product_id option:selected').data('satuan_kecil') || '';
            $('.satuan-box').text(satuan ? satuan.toUpperCase() : 'Satuan');
            $('#add-satuan').val(satuan);
        });

        // Batch/seri/expired change: auto fill harga/diskon/qty if needed
        $('#add-no_seri, #add-tanggal_expired').on('change', function() {
            let productId = $('#add-product_id').val();
            let invoiceId = $('#select-sales-invoice').val();
            let noSeri = $('#add-no_seri').val();
            let expired = $('#add-tanggal_expired').val();
            $.get("/admin/sales/returns/invoice-product-options/" + invoiceId + "/" + productId, function(res) {
                let batch = (res.batches || []).find(b =>
                    b.no_seri == noSeri && b.tanggal_expired == expired
                );
                if (batch) {
                    $('#add-harga_satuan').val(batch.harga_satuan).trigger('input');
                    $('.satuan-box').text(batch.satuan_kecil ? batch.satuan_kecil.toUpperCase() : 'Satuan');
                    $('#add-qty').val(batch.qty || 1).trigger('input');
                    $('#add-sisa_stok').val(batch.sisa_stok || 0).trigger('input');
                    $('#add-sub_total_sblm_disc').val(batch.sub_total_sblm_disc || 0).trigger('input');
                    $('#add-total_diskon_item').val(batch.total_diskon_item || 0).trigger('input');
                    $('#add-sub_total_sebelum_ppn').val(batch.sub_total_sebelum_ppn || 0).trigger('input');
                    $('#add-sub_total_setelah_disc').val(batch.sub_total_setelah_disc || 0).trigger('input');
                    $('#add-ppn_persen').val(batch.ppn_persen || 0).trigger('input');
                    for (let i = 1; i <= 3; i++) {
                        $('#add-diskon_' + i + '_persen').val(batch[`diskon_${i}_persen`] || 0).trigger('input');
                        $('#add-diskon_' + i + '_rupiah').val(batch[`diskon_${i}_rupiah`] || 0).trigger('input');
                    }
                }
            });
        });

        // Kalkulasi diskon/subtotal/PPN (add-item-form only)
        $('#add-item-form').on('input', '.qty-input, .harga-input, .ppn-persen-input, .diskon-persentase-input, .diskon-rupiah-input', function(e) {
            let qty = parseFloat($('#add-qty').val()) || 0;
            let harga = parseFloat($('#add-harga_satuan').val()) || 0;
            let diskonTotalPerQty = 0;
            let hargaSetelahDiskon = harga;
            for (let i = 1; i <= 3; i++) {
                let persenInput = $('#add-diskon_' + i + '_persen');
                let rupiahInput = $('#add-diskon_' + i + '_rupiah');
                let hargaDasar = hargaSetelahDiskon;
                let changed = null;
                if (e.target === persenInput[0]) changed = 'persen';
                if (e.target === rupiahInput[0]) changed = 'rupiah';
                if (changed === 'persen' && hargaDasar > 0) {
                    let vPersen = parseFloat(persenInput.val()) || 0;
                    let hasilRp = hargaDasar * vPersen / 100;
                    rupiahInput.val(hasilRp.toFixed(2));
                }
                if (changed === 'rupiah' && hargaDasar > 0) {
                    let vRupiah = parseFloat(rupiahInput.val()) || 0;
                    let hasilPersen = (vRupiah / hargaDasar) * 100;
                    persenInput.val(hasilPersen.toFixed(2));
                }
                let diskonP = parseFloat(persenInput.val()) || 0;
                let nominal = hargaDasar * diskonP / 100;
                diskonTotalPerQty += nominal;
                hargaSetelahDiskon = hargaDasar - nominal;
            }
            let subtotal = qty * harga;
            $('#add-sub_total_sblm_disc').val(subtotal.toFixed(2));
            let totalDiskonSemuaQty = diskonTotalPerQty * qty;
            $('#add-total_diskon_item').val(totalDiskonSemuaQty.toFixed(2));
            let subtotalSebelumPPN = (harga - diskonTotalPerQty) * qty;
            $('#add-sub_total_sebelum_ppn').val(subtotalSebelumPPN.toFixed(2));
            let ppnPersen = parseFloat($('#add-ppn_persen').val()) || 0;
            let ppnNominal = subtotalSebelumPPN * ppnPersen / 100;
            let subtotalSetelahPPN = subtotalSebelumPPN + ppnNominal;
            $('#add-sub_total_setelah_disc').val(subtotalSetelahPPN.toFixed(2));
        });


        // Add item to table
        $('#btn-add-item').click(function() {
            let item = {
                product_id: $('#add-product_id').val(),
                no_seri: $('#add-no_seri').val(),
                tanggal_expired: $('#add-tanggal_expired').val(),
                qty: $('#add-qty').val(),
                satuan: $('#add-satuan').val(),
                harga_satuan: $('#add-harga_satuan').val(),
                sisa_stok: $('#add-sisa_stok').val(),
                sub_total_sblm_disc: $('#add-sub_total_sblm_disc').val(),
                diskon_1_persen: $('#add-diskon_1_persen').val(),
                diskon_1_rupiah: $('#add-diskon_1_rupiah').val(),
                diskon_2_persen: $('#add-diskon_2_persen').val(),
                diskon_2_rupiah: $('#add-diskon_2_rupiah').val(),
                diskon_3_persen: $('#add-diskon_3_persen').val(),
                diskon_3_rupiah: $('#add-diskon_3_rupiah').val(),
                total_diskon_item: $('#add-total_diskon_item').val(),
                sub_total_sebelum_ppn: $('#add-sub_total_sebelum_ppn').val(),
                ppn_persen: $('#add-ppn_persen').val(),
                sub_total_setelah_disc: $('#add-sub_total_setelah_disc').val(),
                catatan: $('#add-catatan').val()
            };
            if (!item.product_id || !item.qty) {
                alert('Produk dan Qty wajib diisi');
                return;
            }
            // Prevent duplicate product+no_seri+expired
            let dupe = false;
            $('#review-items-table tbody tr').each(function() {
                let idx = $(this).data('index');
                let pid = $(`[name="items[${idx}][product_id]"]`).val();
                let noseri = $(`[name="items[${idx}][no_seri]"]`).val();
                let expired = $(`[name="items[${idx}][tanggal_expired]"]`).val();
                if (pid == item.product_id && noseri == item.no_seri && expired == item.tanggal_expired) dupe = true;
            });
            if (dupe) {
                alert('Item dengan produk, no seri, dan expired yang sama sudah ada!');
                return;
            }
            $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
            $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
            itemIndex++;
            // Clear add form
            $('#add-item-form input, #add-item-form select, #add-item-form textarea').val('');
            $('#add-satuan').val('');
            $('.satuan-box').text('Satuan');
            updateSummary();
        });
        // Remove item
        $('#review-items-table').on('click', '.btn-remove-item', function() {
            let $tr = $(this).closest('tr');
            let idx = $tr.data('index');
            $tr.remove();
            $(`#hidden-inputs-container .item-hidden[data-index="${idx}"]`).remove();
            updateSummary();
        });

        // Block form submit if no item
        $('form').on('submit', function(e) {
            if ($('#review-items-table tbody tr').length === 0) {
                alert('Tambah minimal 1 item sebelum simpan!');
                e.preventDefault();
                return false;
            }
        });
        updateSummary();

        $('#btn-history').on('click', function() {
            let customer_id = $('[name="company_profile_id"]').val();
            let product_id = $('#add-product_id').val();
            if (!customer_id || !product_id) {
                alert('Pilih customer dan produk terlebih dahulu!');
                return;
            }
            // Load via AJAX
            $('#table-history tbody').html('<tr><td colspan="18" class="text-center">Memuat data...</td></tr>');
            $.get("{{ url('admin/stocks/history-penjualan') }}", {
                customer_id: customer_id,
                product_id: product_id
            }, function(res) {
                let rows = '';
                if (!res.length) {
                    rows = '<tr><td colspan="18" class="text-center text-muted">Tidak ada histori penjualan.</td></tr>';
                } else {
                    res.forEach(row => {
                        rows += `<tr>
                  <td>${row.kode}</td>
                  <td>${row.tanggal}</td>
                  <td>${row.customer_nama}</td>
                  <td>${row.produk_nama}</td>
                  <td>${row.qty} ${row.satuan}</td>
                  <td>${Number(row.harga_satuan).toLocaleString()}</td>
                  <td>${row.diskon_1_persen||0}</td>
                  <td>${row.diskon_1_rupiah||0}</td>
                  <td>${row.diskon_2_persen||0}</td>
                  <td>${row.diskon_2_rupiah||0}</td>
                  <td>${row.diskon_3_persen||0}</td>
                  <td>${row.diskon_3_rupiah||0}</td>
                    <td>${Number(row.sub_total_sblm_disc).toLocaleString()}</td>
                    <td>${Number(row.total_diskon_item).toLocaleString()}</td>
                    <td>${Number(row.sub_total_sebelum_ppn).toLocaleString()}</td>
                    <td>${row.ppn_persen||0}</td>
                    <td>${Number(row.sub_total_setelah_disc).toLocaleString()}</td>
                    <td>${row.catatan||''}</td>
                </tr>`;
                    });
                }
                $('#table-history tbody').html(rows);
            });
            $('#modal-history').modal('show');
        });

        

    });    
    // Summary update handled by parent blade as before
</script>
@endpush


@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>

    /* Match Select2 single select to Bootstrap 4/5 .form-control */
    .select2-container--default .select2-selection--single {
        height: 38px !important; /* Default Bootstrap 4/5 input height */
        padding: 6px 12px !important;
        font-size: 1rem !important;
        border: 1px solid #ced4da !important;
        border-radius: 0.25rem !important; /* For Bootstrap 4, use 0.375rem for Bootstrap 5 */
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
