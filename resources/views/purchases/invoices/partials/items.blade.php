@php
// For edit: fill $existingItems from $invoice or old(), fallback kosong array
$existingItems = old('items', isset($invoice) ? $invoice->items->toArray() : []);
@endphp

<div id="add-item-form" class="card p-3 mb-4 border shadow-sm">
    <div class="row">
        <div class="col-md-4 mb-2">
            <label>Produk</label>
            <select id="add-product_id" class="form-control select-product">
                <option value="">-- Pilih Produk --</option>
                @foreach($products as $p)
                <option value="{{ $p->id }}" data-satuan_kecil="{{ $p->satuan_kecil }}">
                    {{ $p->kode }} - {{ $p->nama }}
                </option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2 mb-2">
            <label>No Batch / Seri</label>
            <input id="add-no_seri" type="text" class="form-control">
        </div>
        <div class="col-md-2 mb-2">
            <label>Expired</label>
            <input id="add-tanggal_expired" type="date" class="form-control">
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
    <table class="table table-bordered" id="review-items-table" style="min-width:1600px">
        <thead>
            <tr>
                <th>Produk</th>
                <th>No Batch</th>
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
                <th>Hapus</th>
            </tr>
        </thead>
        <tbody></tbody>
    </table>
</div>
<div id="hidden-inputs-container"></div>



@push('js')
<script>
    $(function(){
let itemIndex = 0;

// Untuk edit: render existing items ke tabel dan hidden input
let existingItems = @json($existingItems);
let products = @json($products->keyBy('id'));

function renderReviewRow(item, idx) {
    let produk = products[item.product_id] ? (products[item.product_id].kode + ' - ' + products[item.product_id].nama) : '';
    return `<tr data-index="${idx}">
        <td style="white-space:nowrap">${produk}</td>
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
        <td><button type="button" class="btn btn-danger btn-sm btn-remove-item"><i class="fa fa-trash"></i> Hapus</button></td>
    </tr>`;
}

function renderHiddenInputs(item, idx) {
    let fields = [
        'product_id', 'no_seri', 'tanggal_expired',
        'qty', 'satuan', 'harga_satuan',
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

// Render data lama jika edit
if (existingItems.length) {
    existingItems.forEach((item, i) => {
        $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
        $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
        itemIndex++;
    });
}

// Add item ke tabel review
$('#btn-add-item').click(function() {
    let item = {
        product_id: $('#add-product_id').val(),
        no_seri: $('#add-no_seri').val(),
        tanggal_expired: $('#add-tanggal_expired').val(),
        qty: $('#add-qty').val(),
        satuan: $('#add-satuan').val(),
        harga_satuan: $('#add-harga_satuan').val(),
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
    if (!item.product_id || !item.qty || !item.harga_satuan) {
        alert('Produk, Qty, dan Harga wajib diisi');
        return;
    }
    $('#review-items-table tbody').append(renderReviewRow(item, itemIndex));
    $('#hidden-inputs-container').append(renderHiddenInputs(item, itemIndex));
    itemIndex++;
    // Bersihkan form input
    $('#add-item-form input, #add-item-form select, #add-item-form textarea').val('');
    $('#add-satuan').val('');
    $('.satuan-box').text('Satuan');
});

// Hapus row dari review
$('#review-items-table').on('click', '.btn-remove-item', function() {
    let $tr = $(this).closest('tr');
    let idx = $tr.data('index');
    $tr.remove();
    $(`#hidden-inputs-container .item-hidden[data-index="${idx}"]`).remove();
});

// Kalkulasi diskon, subtotal, ppn - sesuai sales faktur
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

// Satuan dinamis dari produk
$('#add-product_id').on('change', function() {
    let satuan = $('#add-product_id option:selected').data('satuan_kecil') || '';
    $('.satuan-box').text(satuan ? satuan.toUpperCase() : 'Satuan');
    $('#add-satuan').val(satuan);
});

// Block submit jika belum ada item
$('form').on('submit', function(e) {
    if ($('#review-items-table tbody tr').length === 0) {
        alert('Tambah minimal 1 item sebelum simpan!');
        e.preventDefault();
        return false;
    }
});
    updateSummary();

})
</script>
@endpush

@push('css')
<style>
#review-items-table {
    min-width: 1600px;
}
#review-items-table th, #review-items-table td {
    white-space: nowrap;
}
@media (max-width: 992px) {
    #review-items-table {
        font-size: 13px;
    }
}
</style>
@endpush
