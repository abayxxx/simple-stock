@php
// Selalu ambil old('items') jika validasi gagal, fallback ke $invoice->items (collection), fallback 1 row kosong untuk create.
$items = $items ?? old('items', isset($return) ? $return->items->toArray() : [ [] ]);
@endphp

<div id="items-wrapper">
    @foreach($items as $rowIdx => $item)
    <div class="item-row card p-3 mb-4 border shadow-sm">
        <div class="row">
            <div class="col-md-3 mb-2">
                <label>Produk</label>
                <select name="items[{{ $rowIdx }}][product_id]" class="form-control select-product" required>
                    <option value="">-- Pilih Produk --</option>
                    @foreach($products as $p)
                    <option value="{{ $p->id }}"
                        data-satuan_kecil="{{ $p->satuan_kecil }}"
                        {{ old("items.$rowIdx.product_id", $item['product_id'] ?? '') == $p->id ? 'selected' : '' }}>
                        {{ $p->kode }} - {{ $p->nama }}
                    </option>
                    @endforeach
                </select>
            </div>
            <!-- <div class="col-md-3 mb-2">
                <label>Lokasi</label>
                <select name="items[{{ $rowIdx }}][lokasi_id]" class="form-control select-lokasi" required>
                    <option value="">-- Pilih Lokasi --</option>
                    @foreach($branches as $b)
                    <option value="{{ $b->id }}"
                        {{ old("items.$rowIdx.lokasi_id", $item['lokasi_id'] ?? '') == $b->id ? 'selected' : '' }}>
                        {{ $b->name }}
                    </option>
                    @endforeach
                </select>
            </div> -->
            <!-- NO SERI -->
            <div class="col-md-2 mb-2">
                <label>No Seri</label>
                <select name="items[{{ $rowIdx }}][no_seri]" class="form-control select-no-seri">
                    <option value="">-- Pilih No Seri --</option>
                    @if(!empty($item['product_id']))
                    {{-- Opsional: server-side generate selected no_seri for edit --}}
                    <option value="{{ $item['no_seri'] }}" selected>{{ $item['no_seri'] }}</option>
                    @endif
                </select>
            </div>
            <!-- TGL EXPIRED -->
            <div class="col-md-2 mb-2">
                <label>Expired</label>
                <select name="items[{{ $rowIdx }}][tanggal_expired]" class="form-control select-tanggal-expired">
                    <option value="">-- Pilih Expired --</option>
                    @if(!empty($item['tanggal_expired']))
                    <option value="{{ $item['tanggal_expired'] }}" selected>{{ $item['tanggal_expired'] }}</option>
                    @endif
                </select>
            </div>
        </div>
        <div class="row">
            <div class="col-md-2 mb-2">
                <label>Qty</label>
                <div class="input-group">
                    <input name="items[{{ $rowIdx }}][qty]" type="number" min="1" class="form-control qty-input" required
                        value="{{ old("items.$rowIdx.qty", $item['qty'] ?? '') }}">
                    <span class="input-group-text satuan-box">
                        {{ old("items.$rowIdx.satuan", $item['satuan'] ?? '') ?: 'Satuan' }}
                        <input name="items[{{ $rowIdx }}][satuan]" type="hidden"
                            value="{{ old("items.$rowIdx.satuan", $item['satuan'] ?? '') }}">
                    </span>
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <label>Harga</label>
                <input name="items[{{ $rowIdx }}][harga_satuan]" type="number" step="0.01"
                    class="form-control harga-input" required
                    value="{{ old("items.$rowIdx.harga_satuan", $item['harga_satuan'] ?? '') }}">
            </div>
            <div class="col-md-2 mb-2">
                <label>Sisa Stok</label>
                <input class="form-control sisa-stok-input bg-success bg-opacity-25 fw-bold"
                    value="{{ old("items.$rowIdx.sisa_stok", $item['sisa_stok'] ?? 0) }}" readonly>
            </div>
            <div class="col-md-3 mb-2">
                <label>Subtotal Sebelum Diskon</label>
                <input name="items[{{ $rowIdx }}][sub_total_sblm_disc]" type="number"
                    class="form-control sub-total-sblm-disc bg-success bg-opacity-25" readonly
                    value="{{ old("items.$rowIdx.sub_total_sblm_disc", $item['sub_total_sblm_disc'] ?? 0) }}">
            </div>
        </div>
        <div class=row>
            <div class="col-md-3 mb-2">
                <label>Diskon (%)</label>
                <div class="d-flex gap-1">
                    @for($i=1;$i<=3;$i++)
                        <input name="items[{{ $rowIdx }}][diskon_{{$i}}_persen]" type="number" step="0.01"
                        class="form-control diskon-persentase-input"
                        placeholder="D{{ $i }}"
                        value="{{ old("items.$rowIdx.diskon_{$i}_persen", $item["diskon_{$i}_persen"] ?? '') }}">
                        @endfor
                </div>
            </div>
            <div class="col-md-3 mb-2">
                <label>Diskon Harga Satuan</label>
                <div class="d-flex gap-1">
                    @for($i=1;$i<=3;$i++)
                        <input name="items[{{ $rowIdx }}][diskon_{{$i}}_rupiah]" type="number" step="0.01"
                        class="form-control diskon-rupiah-input"
                        placeholder="Rp D{{ $i }}"
                        value="{{ old("items.$rowIdx.diskon_{$i}_rupiah", $item["diskon_{$i}_rupiah"] ?? '') }}">
                        @endfor
                </div>
            </div>
            <div class="col-md-2 mb-2">
                <label>Total Diskon Item</label>
                <input name="items[{{ $rowIdx }}][total_diskon_item]" type="number"
                    class="form-control total-diskon-item-input bg-success bg-opacity-25" readonly
                    value="{{ old("items.$rowIdx.total_diskon_item", $item['total_diskon_item'] ?? 0) }}">
            </div>
            <div class="col-md-2 mb-2">
                <label>Subtotal Sebelum PPN</label>
                <input name="items[{{ $rowIdx }}][sub_total_sebelum_ppn]" type="number"
                    class="form-control sub-total-sebelum-ppn-input bg-success bg-opacity-25" readonly
                    value="{{ old("items.$rowIdx.sub_total_sebelum_ppn", $item['sub_total_sebelum_ppn'] ?? 0) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-3 mb-2">
                <label>PPN (%)</label>
                <input name="items[{{ $rowIdx }}][ppn_persen]" type="number" step="0.01"
                    class="form-control ppn-persen-input"
                    value="{{ old("items.$rowIdx.ppn_persen", $item['ppn_persen'] ?? 0) }}">
            </div>
            <div class="col-md-3 mb-2">
                <label>Subtotal Setelah PPN</label>
                <input name="items[{{ $rowIdx }}][sub_total_setelah_disc]" type="number"
                    class="form-control sub-total-setelah-disc-input bg-success bg-opacity-25" readonly
                    value="{{ old("items.$rowIdx.sub_total_setelah_disc", $item['sub_total_setelah_disc'] ?? 0) }}">
            </div>
        </div>
        <div class="row">
            <div class="col-md-10 mb-2">
                <label>Catatan</label>
                <textarea name="items[{{ $rowIdx }}][catatan]" class="form-control">{{ old("items.$rowIdx.catatan", $item['catatan'] ?? '') }}</textarea>
            </div>
            <div class="col-md-2 d-flex align-items-end mb-2">
                <button type="button" class="btn btn-danger btn-remove-row w-100"><i class="fa fa-trash"></i> Hapus</button>
            </div>
        </div>
    </div>
    @endforeach
</div>
<button type="button" class="btn btn-success" id="add-item-row"><i class="fa fa-plus"></i> Tambah Barang</button>


@push('js')
<script>
    $(function() {
        let rowIdx = $('#items-wrapper .item-row').length;

        // Tambah row baru
        $('#add-item-row').on('click', function() {
            let $lastRow = $('#items-wrapper .item-row:last');
            let newRow = $lastRow.clone();
            newRow.find('input, select, textarea').each(function() {
                let name = $(this).attr('name');
                if (name) {
                    name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                    $(this).attr('name', name).val('');
                }
            });
            newRow.find('.sisa-stok-input').val(0);
            $('#items-wrapper').append(newRow);
            rowIdx++;
        });

        // Hapus row
        $('#items-wrapper').on('click', '.btn-remove-row', function() {
            if ($('#items-wrapper .item-row').length > 1)
                $(this).closest('.item-row').remove();
        });

        // Live kalkulasi (termasuk sinkronisasi diskon 1-3 dua arah)
        $('#items-wrapper').on('input',
            '.qty-input, .harga-input, .ppn-persen-input, .diskon-persentase-input, .diskon-rupiah-input',
            function(e) {
                let $row = $(this).closest('.item-row');
                let qty = parseFloat($row.find('.qty-input').val()) || 0;
                let harga = parseFloat($row.find('.harga-input').val()) || 0;

                // Diskon bertingkat: Untuk setiap level, hanya satu value yang digunakan (yang terakhir diubah user)
                let diskonTotalPerQty = 0;
                let hargaSetelahDiskon = harga;

                for (let i = 1; i <= 3; i++) {
                    let persenInput = $row.find('[name*="[diskon_' + i + '_persen]"]');
                    let rupiahInput = $row.find('[name*="[diskon_' + i + '_rupiah]"]');
                    let hargaDasar = hargaSetelahDiskon;

                    let changed = null;
                    if (e.target === persenInput[0]) changed = 'persen';
                    if (e.target === rupiahInput[0]) changed = 'rupiah';

                    if (changed === 'persen' && hargaDasar > 0) {
                        // Saat persen diubah, update rupiah
                        let vPersen = parseFloat(persenInput.val()) || 0;
                        let hasilRp = hargaDasar * vPersen / 100;
                        rupiahInput.val(hasilRp.toFixed(2));
                    }
                    if (changed === 'rupiah' && hargaDasar > 0) {
                        // Saat rupiah diubah, update persen
                        let vRupiah = parseFloat(rupiahInput.val()) || 0;
                        let hasilPersen = (vRupiah / hargaDasar) * 100;
                        persenInput.val(hasilPersen.toFixed(2));
                    }

                    // Ambil value diskon (yang terakhir diubah user)
                    let diskonP = parseFloat(persenInput.val()) || 0;
                    let diskonR = parseFloat(rupiahInput.val()) || 0;
                    // Di sini, selalu hitung dari salah satu (hasil sync), karena selalu konsisten nilainya
                    let nominal = hargaDasar * diskonP / 100; // atau diskonR, hasilnya sama
                    diskonTotalPerQty += nominal;
                    hargaSetelahDiskon = hargaDasar - nominal;
                }

                // Kalkulasi subtotal dan diskon total
                let subtotal = qty * harga;
                $row.find('.sub-total-sblm-disc').val(subtotal.toFixed(2));

                let totalDiskonSemuaQty = diskonTotalPerQty * qty;
                $row.find('.total-diskon-item-input').val(totalDiskonSemuaQty.toFixed(2));
                let subtotalSebelumPPN = (harga - diskonTotalPerQty) * qty;
                $row.find('.sub-total-sebelum-ppn-input').val(subtotalSebelumPPN.toFixed(2));

                let ppnPersen = parseFloat($row.find('.ppn-persen-input').val()) || 0;
                let ppnNominal = subtotalSebelumPPN * ppnPersen / 100;
                let subtotalSetelahPPN = subtotalSebelumPPN + ppnNominal;
                $row.find('.sub-total-setelah-disc-input').val(subtotalSetelahPPN.toFixed(2));
            });



        // Sisa stok live
        $('#items-wrapper').on('input change', '.select-product, .select-lokasi, .qty-input', function() {
            console.log('Sisa stok updated');
            let $row = $(this).closest('.item-row');
            let product_id = $row.find('.select-product').val();
            let lokasi_id = $row.find('.select-lokasi').val();
            if (product_id) {
                $.get("{{ url('admin/stocks/get-sisa-stok') }}/" + product_id + "?lokasi_id=" + lokasi_id, function(res) {
                    $row.find('.sisa-stok-input').val(Number(res) + Number($row.find('.qty-input').val()));
                });
            } else {
                $row.find('.sisa-stok-input').val(0);
            }
        });



        let batchesData = {};

        function fetchBatchOptions($row) {
            let productOptions = $row.find('.select-product');
            let productId = productOptions.val();
            let itemsId = productOptions.data('items-id') || null;
            let salesInvoiceId = $('[name="sales_invoice_id"]').val();

            if (!productId || !salesInvoiceId) return;

            $.get("/admin/sales/returns/invoice-product-options/" + salesInvoiceId + "/" + productId, function(res) {
                batchesData[productId] = res.batches || [];

                // Fill no_seri and tanggal_expired
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


                $row.find('.select-no-seri').html(noSeriOpts);
                $row.find('.select-tanggal-expired').html(tglExpOpts);
            });
        }

        // When both selected, auto-fill harga
        function fillHargaIfNeeded($row) {
            let productId = $row.find('.select-product').val();
            let noSeri = $row.find('.select-no-seri').val();
            let expired = $row.find('.select-tanggal-expired').val();

            let batch = (batchesData[productId] || []).find(b =>
                b.no_seri == noSeri && b.tanggal_expired == expired
            );
            if (batch) {
                $row.find('.harga-input').val(batch.harga_satuan).trigger('input'); // Trigger input to recalculate
                $row.find('.satuan-box').text(batch.satuan_kecil ? batch.satuan_kecil.toUpperCase() : 'Satuan');
                $row.find('.qty-input').val(batch.qty || 1).trigger('input'); // Set qty to batch qty if available
                $row.find('.sisa-stok-input').val(batch.sisa_stok || 0).trigger('input');
                $row.find('.sub-total-sblm-disc').val(batch.sub_total_sblm_disc || 0).trigger('input');
                $row.find('.total-diskon-item-input').val(batch.total_diskon_item || 0).trigger('input');
                $row.find('.sub-total-sebelum-ppn-input').val(batch.sub_total_sebelum_ppn || 0).trigger('input');
                $row.find('.sub-total-setelah-disc-input').val(batch.sub_total_setelah_disc || 0).trigger('input');
                $row.find('.ppn-persen-input').val(batch.ppn_persen || 0).trigger('input');

                // Update diskon inputs if available
                for (let i = 1; i <= 3; i++) {
                    $row.find(`[name="items[${$row.index()}][diskon_${i}_persen]"]`).val(batch[`diskon_${i}_persen`] || 0).trigger('input');
                    $row.find(`[name="items[${$row.index()}][diskon_${i}_rupiah]"]`).val(batch[`diskon_${i}_rupiah`] || 0).trigger('input');
                }

            }
        }

        // Event wiring example
        $('#items-wrapper').on('change', '.select-product', function() {
            fetchBatchOptions($(this).closest('.item-row'));
        });
        $('#items-wrapper').on('change', '.select-no-seri, .select-tanggal-expired', function() {
            fillHargaIfNeeded($(this).closest('.item-row'));
        });

        // On change produk/lokasi → fetch no_seri, tgl_expired, harga
        // $('#items-wrapper').on('change', '.select-product, .select-lokasi', function() {
        //     fetchStockData($(this).closest('.item-row'));
        // });

        $('#items-wrapper').on('change', '.select-product', function() {
            var $row = $(this).closest('.item-row');
            var satuan = $(this).find('option:selected').data('satuan_kecil') || '';
            $row.find('.satuan-box').text(satuan ? satuan.toUpperCase() : 'Satuan');
        });

        // On page load (for edit):
        $('#items-wrapper .item-row').each(function() {
            var $row = $(this);
            var $select = $row.find('.select-product');
            var satuan = $select.find('option:selected').data('satuan_kecil') || '';
            $row.find('.satuan-box').text(satuan ? satuan.toUpperCase() : 'Satuan');
        });
    });
</script>
@endpush