@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
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
        <label>Customer</label>
        <select name="company_profile_id" class="form-control" id="select-customer" required>
            <option value="">-- Pilih Customer --</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ old('company_profile_id', $return->company_profile_id ?? '') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>
        @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label>Sales Group</label>
        <select name="sales_group_id" class="form-control" id="select-sales-group">
            <option value="">-- Pilih Sales Group --</option>
            @foreach($salesGroups as $g)
            <option value="{{ $g->id }}" {{ old('sales_group_id', $return->sales_group_id ?? '') == $g->id ? 'selected' : '' }}>
                {{ $g->nama }}
            </option>
            @endforeach
        </select>
        @error('sales_group_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
</div>
<div class="row mb-3">
    <div class="col-md-3">
        <label>No Faktur Jual</label>
        <select name="sales_invoice_id" class="form-control" id="select-sales-invoice">
            <option value="">-- Pilih Faktur --</option>
            @foreach($invoices as $inv)
            <option value="{{ $inv->id }}" {{ old('sales_invoice_id', $return->sales_invoice_id ?? '') == $inv->id ? 'selected' : '' }}
                data-cust="{{ $inv->company_profile_id }}"
                data-salesgroup="{{ $inv->sales_group_id }}">
                {{ $inv->kode }}
            </option>
            @endforeach
        </select>
        @error('sales_invoice_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
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

@include('sales.returns.partials.items', [
'products' => $products,
'branches' => $branches,
'return' => $return ?? null,
'items' => $items ?? null,
])
@include('sales.returns.partials.summary', ['return' => $return ?? null])

<div class="mt-4">
    <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
    <a href="{{ route('sales.returns.index') }}" class="btn btn-secondary">Batal</a>
</div>

@section('js')
@vite(['resources/js/numberFormatter.js'])
@vite(['resources/js/filledOption.js'])
@endsection
@push('js')
<script>
    function fetchInvoices() {
        let customerId = $('#select-customer').val();
        let salesGroupId = $('#select-sales-group').val();
        $.get("{{ url('admin/sales/returns/filter-invoices') }}", {
            customer_id: customerId,
            sales_group_id: salesGroupId
        }, function(res) {
            let options = '<option value="">-- Pilih Faktur --</option>';
            res.invoices.forEach(function(inv) {
                options += `<option value="${inv.id}" data-cust="${inv.customer_id}" data-salesgroup="${inv.sales_group_id}">${inv.kode} - ${inv.customer_name}</option>`;
            });
            $('#select-sales-invoice').html(options);
        });
    }

    $('#select-customer, #select-sales-group').on('change', function() {
        fetchInvoices();
        // Optionally clear the selected invoice if changing customer/group
        $('#select-sales-invoice').val('').trigger('change');
    });

    // --- 2. AUTOFILL CUSTOMER/SALES GROUP IF PICK INVOICE DIRECTLY ---
    $('#select-sales-invoice').on('change', function() {
        let selected = $(this).find('option:selected');
        let custId = selected.data('cust');
        let salesGroupId = selected.data('salesgroup');

        if (custId) $('#select-customer').val(custId);
        if (salesGroupId) $('#select-sales-group').val(salesGroupId);

        // --- 3. Update product dropdowns in items (as before) ---
        let invoiceId = $(this).val();
        if (!invoiceId) return;
        $.get("{{ url('admin/sales/returns/invoice-products-options') }}/" + invoiceId, function(res) {
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
    if ($('#select-sales-invoice').val()) {
        $('#select-sales-invoice').trigger('change');
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

        // Loop semua item di tabel review
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

        // Ambil diskon faktur & PPN tambahan (jika diisi user)
        let diskonFaktur = parseFloat($('[name="diskon_faktur"]').val()) || 0;
        let diskonPPN = parseFloat($('[name="diskon_ppn"]').val()) || 0;

        // Hitung grand total setelah diskon faktur dan diskon ppn (jika ada)
        let grandTotalWithDiskon = grandTotal - (grandTotal * (diskonFaktur / 100)) + (grandTotal * (diskonPPN / 100));

        // Hitung total bayar (default = grandTotalWithDiskon)
        let sisaTagihan = grandTotalWithDiskon;
        

        // Set value ke summary
        $('[name="subtotal"]').val(subtotal);
        $('#subtotal_display').val(subtotal.toLocaleString('id-ID'));
        $('[name="diskon_item"]').val(totalDiskonItem);
        $('#diskon_item_display').val(totalDiskonItem.toLocaleString('id-ID'));
        $('[name="subtotal_sebelum_ppn"]').val(subtotalSebelumPPN);
        $('#subtotal_sebelum_ppn_display').val(subtotalSebelumPPN.toLocaleString('id-ID'));
        $('[name="grand_total"]').val(grandTotalWithDiskon);
        $('#grand_total_display').val(grandTotalWithDiskon.toLocaleString('id-ID'));
        // $('[name="total_bayar"]').val(0);
        // $('#total_bayar_display').val(0);
        $('[name="sisa_tagihan"]').val(sisaTagihan);
        $('#sisa_tagihan_display').val(sisaTagihan.toLocaleString('id-ID'));
    }


    // Trigger summary update setiap ada perubahan di item atau summary field
    $('[name="diskon_faktur"], [name="diskon_ppn"], [name="total_bayar"]').on('input keyup change', updateSummary);

    // Panggil pertama kali saat page ready
</script>
@endpush