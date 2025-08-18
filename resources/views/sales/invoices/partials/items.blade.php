@php
// For edit: fill $existingItems from $invoice or old()
$existingItems = old('items', isset($invoice) ? $invoice->items->toArray() : []);
@endphp

<div id="add-item-form" class="card p-3 mb-4 border shadow-sm">
    <div class="row">
        <div class="col-md-3 mb-2">
            <div class="d-flex ">
                <label>Produk</label>
                <button type="button" class="btn btn-link p-0 ms-1 ml-1" id="btn-history" style="font-size:1.15em;">
                    <i class="fa fa-info-circle text-info"></i>
                </button>
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

<!-- Table Review Items -->
<div style="overflow-x: auto;">
    <table class="table table-bordered" id="review-items-table" style="min-width:1800px">
        <thead>
            <tr>
                <th>Produk</th>
                <th>No Seri</th>
                <th>Expired</th>
                <th>Qty</th>
                <th>Satuan</th>
                <th>Harga</th>
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
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <!-- JS will add rows here -->
        </tbody>
    </table>
</div>


<!-- Hidden inputs will be placed here -->
<div id="hidden-inputs-container"></div>

@include('sales.invoices.partials.modal-history')

@push('js')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script src="{{ asset('js/helper.js') }}"></script>
<script>
    let itemIndex = 0;

    // For edit: load items from backend
    let existingItems = @json($existingItems);
    let products = @json(collect($products)->keyBy('id'));
    let branches = @json(collect($branches)->keyBy('id'));

    function renderReviewRow(item, idx) {
         let produk = products[item.product_id] ? (products[item.product_id].kode + ' - ' + products[item.product_id].nama) : item.product_name;
        let lokasi = branches[item.lokasi_id] ? branches[item.lokasi_id].name : '';
        return `<tr data-index="${idx}">
        <td style="white-space:nowrap" data-product-id="${item.product_id}">${produk}</td>
        <td>${item.no_seri||''}</td>
        <td>${item.tanggal_expired||''}</td>
        <td>${item.qty||''}</td>
        <td>${(item.satuan||'').toUpperCase()}</td>
        <td>${item.harga_satuan||''}</td>
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
  
        <td>
        <button type="button" class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i></button>
        <button type="button" class="btn btn-primary btn-sm btn-edit-item"><i class="fa fa-edit"></i></button>
        </td>
    </tr>`;
    }

    function renderHiddenInputs(item, idx) {
        // All field keys you need for submission
        let fields = [
            'product_id', 'lokasi_id', 'no_seri', 'tanggal_expired',
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

    // On load, render existing items (for edit)
    if (existingItems.length) {
        existingItems.forEach((item, i) => {
            $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
            $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
            itemIndex++;

        });
    }

    // Add new item
    $('#btn-add-item').click(function() {
        let item = {
            product_id: $('#add-product_id').val(),
            product_name: $('#add-product_id option:selected').text(),
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
        // Validation example
        if (!item.product_id  || !item.qty || !item.harga_satuan ) {
            alert('Lengkapi data item terlebih dahulu.');
            return;
        }
        // Add to table and hidden input
        $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
        $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
        itemIndex++;

        // Clear form
        $('#add-item-form input, #add-item-form select, #add-item-form textarea').val('');
        $('#add-satuan').val('');
        $('.satuan-box').text('Satuan');

        updateSummary(); // Call this at the end!
    });

    // Remove row
    $('#review-items-table').on('click', '.btn-remove-item', function() {
        let $tr = $(this).closest('tr');
        let idx = $tr.data('index');
        $tr.remove();
        $(`#hidden-inputs-container .item-hidden[data-index="${idx}"]`).remove();

        updateSummary(); // Update summary after removal
    });

    // Edit Row
   $('#review-items-table').on('click', '.btn-edit-item', function() {
        let $tr = $(this).closest('tr');
        let idx = $tr.data('index');
        let item = {
            product_id: $tr.find('td:eq(0)').data('product-id'),
            no_seri: $tr.find('td:eq(1)').text(),
            tanggal_expired: $tr.find('td:eq(2)').text(),
            qty: $tr.find('td:eq(3)').text(),
            satuan: $tr.find('td:eq(4)').text(),
            harga_satuan: $tr.find('td:eq(5)').text(),
            sub_total_sblm_disc: $tr.find('td:eq(6)').text(),
            diskon_1_persen: $tr.find('td:eq(7)').text(),
            diskon_1_rupiah: $tr.find('td:eq(8)').text(),
            diskon_2_persen: $tr.find('td:eq(9)').text(),
            diskon_2_rupiah: $tr.find('td:eq(10)').text(),
            diskon_3_persen: $tr.find('td:eq(11)').text(),
            diskon_3_rupiah: $tr.find('td:eq(12)').text(),
            total_diskon_item: $tr.find('td:eq(13)').text(),
            sub_total_sebelum_ppn: $tr.find('td:eq(14)').text(),
            ppn_persen: $tr.find('td:eq(15)').text(),
            sub_total_setelah_disc: $tr.find('td:eq(16)').text(),
            catatan: $tr.find('td:eq(18)').text()
        };
         // Prefill Select2 correctly
        const p = products[item.product_id] || { id: item.product_id, kode: '', nama: item.product_name || '', satuan_kecil: '' };
        console.log('Editing item:', item, p, products);
        select2SetProduct($('#add-product_id'), p);
        // Populate form with item data
        $('#add-product_id').val(item.product_id).trigger('change');
        $('#add-no_seri').val(item.no_seri);
        $('#add-tanggal_expired').val(item.tanggal_expired);
        $('#add-qty').val(item.qty);
        $('#add-satuan').val(item.satuan);
        $('#add-harga_satuan').val(item.harga_satuan);
        $('#add-sisa_stok').val(item.sisa_stok);
        $('#add-sub_total_sblm_disc').val(item.sub_total_sblm_disc);
        $('#add-diskon_1_persen').val(item.diskon_1_persen);
        $('#add-diskon_1_rupiah').val(item.diskon_1_rupiah);
        $('#add-diskon_2_persen').val(item.diskon_2_persen);
        $('#add-diskon_2_rupiah').val(item.diskon_2_rupiah);
        $('#add-diskon_3_persen').val(item.diskon_3_persen);
        $('#add-diskon_3_rupiah').val(item.diskon_3_rupiah);
        $('#add-total_diskon_item').val(item.total_diskon_item);
        $('#add-sub_total_sebelum_ppn').val(item.sub_total_sebelum_ppn);
        $('#add-ppn_persen').val(item.ppn_persen);
        $('#add-sub_total_setelah_disc').val(item.sub_total_setelah_disc);
        $('#add-catatan').val(item.catatan);

        // Remove the row from review table
       // Remove the row from table
            $tr.remove();
            // Remove hidden inputs
            $(`#hidden-inputs-container .item-hidden[data-index="${idx}"]`).remove();
            // Update item index
            itemIndex--;
            // Update summary
            updateSummary();
    });

    // Your kalkulasi/stock logic (adapted for single form)
    // --- (Paste your kalkulasi, AJAX, satuan change, etc, here. Just prefix the selectors with #add-...)
    // Example: when product/lokasi changes, fetch No Seri, Expired, Harga, update satuan, etc

    function fetchStockData() {
        let productId = $('#add-product_id').val();
        let lokasiId = $('#add-lokasi_id').val();
        if (!productId) {
            $('#add-no_seri').html('<option value="">-- Pilih No Seri --</option>');
            $('#add-tanggal_expired').html('<option value="">-- Pilih Expired --</option>');
            $('#add-harga_satuan').val('');
            return;
        }
        $.get("{{ url('admin/stocks/product-options') }}/" + productId + "?lokasi_id=" + lokasiId, function(res) {
            let noSeriOpts = '<option value="">-- Pilih No Seri --</option>';
            let tglExpOpts = '<option value="">-- Pilih Expired --</option>';
            if (res.no_seri && res.no_seri.length) {
                res.no_seri.forEach(function(n) {
                    noSeriOpts += `<option value="${n}">${n}</option>`;
                });
            }
            if (res.tanggal_expired && res.tanggal_expired.length) {
                res.tanggal_expired.forEach(function(t) {
                    tglExpOpts += `<option value="${t}">${t}</option>`;
                });
            }
            $('#add-no_seri').html(noSeriOpts);
            $('#add-tanggal_expired').html(tglExpOpts);

            if (res.harga) {
                $('#add-harga_satuan').val(res.harga);
            }
        });
    }

    $('#add-product_id, #add-lokasi_id').on('change', function() {
       let data = $('#add-product_id').select2('data')[0] || {};
  // fallback to option’s data-* attribute
        let optDataSatuan = $('#add-product_id option:selected').data('satuan_kecil');
        let satuanKecil = data.satuan_kecil || optDataSatuan || '';
        $('.satuan-box').text(satuanKecil ? satuanKecil.toUpperCase() : 'Satuan');
        $('#add-satuan').val(satuanKecil || '');
        $('#add-qty').val(0); // Reset qty to 1 
        fetchStockData();
    });



    // Sisa stok live
    $('#add-product_id, #add-lokasi_id, #add-qty').on('change input', function() {
        let product_id = $('#add-product_id').val();
        let lokasi_id = $('#add-lokasi_id').val();
        if (product_id) {
            $.get("{{ url('admin/stocks/get-sisa-stok') }}/" + product_id + "?lokasi_id=" + lokasi_id, function(res) {
                $('#add-sisa_stok').val(Number(res) - Number($('#add-qty').val() || 0));
            });
        } else {
            $('#add-sisa_stok').val(0);
        }
    });

    // Kalkulasi logic for diskon, subtotal, etc (copy your logic, adapt selectors to #add-...)
    $('#add-item-form').on('input change', '.qty-input, .harga-input, .ppn-persen-input, .diskon-persentase-input, .diskon-rupiah-input, .select-product', function(e) {
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

    $('form').on('submit', function(e) {
        if ($('#review-items-table tbody tr').length === 0) {
            alert('Tambah minimal 1 item sebelum simpan!');
            e.preventDefault();
            return false;
        }
    });

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

    $('#add-product_id').select2({
    placeholder: '-- Pilih Produk --',
    minimumInputLength: 2,
    ajax: {
        url: '{{ url("admin/products/search") }}',
        dataType: 'json',
        delay: 250,
        data: function (params) {
            return {
                q: params.term // user typed text
            };
        },
        processResults: function (data) {
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

</script>
@endpush

@push('css')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<style>
    #review-items-table {
        min-width: 1800px;
    }

    #review-items-table th,
    #review-items-table td {
        white-space: nowrap;
    }

    @media (max-width: 992px) {
        #review-items-table {
            font-size: 13px;
        }
    }

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