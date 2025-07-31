<div class="row mb-3">
    <div class="col-md-3 mb-3">
        <label>No Faktur</label>
        <div class="input-group">
            <input
                name="kode"
                id="input-kode"
                class="form-control @error('kode') is-invalid @enderror"
                value="{{ old('kode', $return->kode ?? '') ?: '(auto)' }}"
                @if(old('auto_kode', is_null(old('kode', $return->kode ?? null)) || old('kode', $return->kode ?? '') === '' ? 1 : 0)) readonly @endif
            autocomplete="off"
            @if($return && $return->kode) disabled @endif
            >
            <div class="input-group-text bg-light">
                <input
                    type="checkbox"
                    id="auto_kode"
                    name="auto_kode"
                    value="1"
                    class="form-check-input mt-0"
                    {{ old('auto_kode', (is_null(old('kode', $return->kode ?? null)) || old('kode', $return->kode ?? '') === '') ? 1 : 0) ? 'checked' : '' }}
                    @if($return && $return->kode) disabled @endif
                >
                <label for="auto_kode" class="mb-0 ms-1" style="font-size: 0.93em; cursor:pointer;">Auto</label>
            </div>
        </div>
        @error('kode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label>Tanggal</label>
        <input name="tanggal" type="date" class="form-control" value="{{ old('tanggal', $return->tanggal ?? date('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label>Supplier</label>
        <select name="company_profile_id" class="form-control" id="select-supplier" required>
            <option value="">-- Pilih Supplier --</option>
            @foreach($suppliers as $c)
            <option value="{{ $c->id }}" {{ old('company_profile_id', $return->company_profile_id ?? '') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>
        @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>

</div>
<div class="row mb-3">
    <div class="col-md-3">
        <label>No Faktur Beli</label>
        <select name="purchases_invoice_id" class="form-control" id="select-purchases-invoice">
            <option value="">-- Pilih Faktur --</option>
            @foreach($invoices as $inv)
            <option value="{{ $inv->id }}" {{ old('purchases_invoice_id', $return->purchases_invoice_id ?? '') == $inv->id ? 'selected' : '' }}
                data-cust="{{ $inv->company_profile_id }}">
                {{ $inv->kode }}
            </option>
            @endforeach
        </select>
        @error('purchase_invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label>Tipe Retur</label>
        <select name="tipe_retur" class="form-control">
            <option value="POTONG PIUTANG" {{ old('tipe_retur', $return->tipe_retur ?? '') == 'POTONG PIUTANG' ? 'selected' : '' }}>POTONG PIUTANG</option>
            <option value="UANG KEMBALI" {{ old('tipe_retur', $return->tipe_retur ?? '') == 'UANG KEMBALI' ? 'selected' : '' }}>UANG KEMBALI</option>
        </select>
    </div>
    <div class="col-md-3">
        <label>Catatan</label>
        <input name="catatan" class="form-control" value="{{ old('catatan', $return->catatan ?? '') }}">
    </div>
</div>

@include('purchases.returns.partials.items', [
'products' => $products,
'branches' => $branches,
'return' => $return ?? null,
'items' => $items ?? null,
])
@include('purchases.returns.partials.summary', ['return' => $return ?? null])

<div class="mt-4">
    <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
    <a href="{{ route('purchases.returns.index') }}" class="btn btn-secondary">Batal</a>
</div>

@push('js')
<script>
    function fetchInvoices() {
        let supplierId = $('#select-supplier').val();
        $.get("{{ url('admin/purchases/returns/filter-invoices') }}", {
            supplier_id: supplierId,
        }, function(res) {
            let options = '<option value="">-- Pilih Faktur --</option>';
            console.log(res)

            res.invoices.forEach(function(inv) {
                options += `<option value="${inv.id}" data-cust="${inv.supplier_id}">${inv.kode}</option>`;
            });
            $('#select-purchases-invoice').html(options);
        });
    }

    $('#select-supplier, #select-purchases-group').on('change', function() {
        fetchInvoices();
        // Optionally clear the selected invoice if changing supplier/group
        $('#select-purchases-invoice').val('').trigger('change');
    });

    // --- 2. AUTOFILL supplier/purchases GROUP IF PICK INVOICE DIRECTLY ---
    $('#select-purchases-invoice').on('change', function() {
        let selected = $(this).find('option:selected');
        let custId = selected.data('cust');

        if (custId) $('#select-supplier').val(custId);

        // --- 3. Update product dropdowns in items (as before) ---
        let invoiceId = $(this).val();
        if (!invoiceId) return;
        $.get("{{ url('admin/purchases/returns/invoice-products-options') }}/" + invoiceId, function(res) {
            let options = '<option value="" data-satuan_kecil="">-- Pilih Produk --</option>';
            $.each(res.products, function(i, prod) {
                options += `<option value="${prod.id}" data-satuan_kecil="${prod.satuan || "Satuan"}">${prod.text}</option>`;
            });
            $('#items-wrapper .select-product').each(function() {
                let selected = $(this).val();
                $(this).html(options).val(selected);
            });
        });
    });

    // --- 4. Optionally, trigger on load if editing ---
    if ($('#select-purchases-invoice').val()) {
        $('#select-purchases-invoice').trigger('change');
    }

    // --- 5. Auto toggle kode faktur ---
    function toggleKodeFaktur() {
        if ($('#auto_kode').is(':checked')) {
            $('#input-kode').prop('readonly', true).val('(auto)');
        } else {
            $('#input-kode').prop('readonly', false);
            if ($('#input-kode').val() === '(auto)') {
                $('#input-kode').val('');
            }
        }
    }
    toggleKodeFaktur();
    $('#auto_kode').on('change', toggleKodeFaktur);
    
function updateSummary() {
    let subtotal = 0;
    let totalDiskonItem = 0;
    let subtotalSebelumPPN = 0;
    let totalPPN = 0;
    let grandTotal = 0;

    // Loop setiap row pada table review-items-table
    $('#review-items-table tbody tr').each(function() {
        let idx = $(this).data('index');
        let prefix = `items[${idx}]`;

        let subTotalSblmDisc = parseFloat($(`[name="${prefix}[sub_total_sblm_disc]"]`).val()) || 0;
        let totalDiskon = parseFloat($(`[name="${prefix}[total_diskon_item]"]`).val()) || 0;
        let subTotalSblmPPN = parseFloat($(`[name="${prefix}[sub_total_sebelum_ppn]"]`).val()) || 0;
        let subTotalStlhDisc = parseFloat($(`[name="${prefix}[sub_total_setelah_disc]"]`).val()) || 0;

        subtotal += subTotalSblmDisc;
        totalDiskonItem += totalDiskon;
        subtotalSebelumPPN += subTotalSblmPPN;
        grandTotal += subTotalStlhDisc;

        let ppnPerItem = subTotalStlhDisc - subTotalSblmPPN;
        totalPPN += ppnPerItem;
    });

    // Diskon faktur & tambahan PPN (jika ada)
    let diskonFaktur = parseFloat($('[name="diskon_faktur"]').val()) || 0;
    let diskonPPN = parseFloat($('[name="diskon_ppn"]').val()) || 0;

    // Hitung grand total setelah diskon faktur dan ppn
    let grandTotalWithDiskon = grandTotal - (grandTotal * (diskonFaktur / 100)) + (grandTotal * (diskonPPN / 100));
    let sisaTagihan = grandTotalWithDiskon;

    // Set value ke summary fields
    $('[name="subtotal"]').val(subtotal.toFixed(2));
    $('[name="diskon_item"]').val(totalDiskonItem.toFixed(2));
    $('[name="subtotal_sebelum_ppn"]').val(subtotalSebelumPPN.toFixed(2));
    $('[name="grand_total"]').val(grandTotalWithDiskon.toFixed(2));
    $('[name="total_bayar"]').val(0);
    $('[name="sisa_tagihan"]').val(sisaTagihan.toFixed(2));
}


    // Trigger summary update setiap ada perubahan di item atau summary field
    $('[name="diskon_faktur"], [name="diskon_ppn"], [name="total_bayar"]').on('input keyup change', updateSummary);

    // Panggil pertama kali saat page ready
    updateSummary();
</script>
@endpush