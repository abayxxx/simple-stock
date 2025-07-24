@php $invoice = $invoice ?? null; @endphp

<div class="row mb-3">

    <div class="col-md-3 mb-3">

        <label>No Faktur</label>
        <div class="input-group">
            <input
                name="kode"
                id="input-kode"
                class="form-control @error('kode') is-invalid @enderror"
                value="{{ old('kode', $invoice->kode ?? '') ?: '(auto)' }}"
                @if(old('auto_kode', is_null(old('kode', $invoice->kode ?? null)) || old('kode', $invoice->kode ?? '') === '' ? 1 : 0)) readonly @endif
            autocomplete="off"
            @if($invoice && $invoice->kode) disabled @endif

            >
            <div class="input-group-text bg-light">
                <input
                    type="checkbox"
                    id="auto_kode"
                    name="auto_kode"
                    value="1"
                    class="form-check-input mt-0"
                    {{ old('auto_kode', (is_null(old('kode', $invoice->kode ?? null)) || old('kode', $invoice->kode ?? '') === '') ? 1 : 0) ? 'checked' : '' }}
                    @if($invoice && $invoice->kode) disabled @endif
                >
                <label for="auto_kode" class="mb-0 ms-1" style="font-size: 0.93em; cursor:pointer;">Auto</label>
            </div>
        </div>
        @error('kode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>

    <div class="col-md-2">
        <label>Tanggal</label>
        <input name="tanggal" type="date" value="{{ old('tanggal', $invoice->tanggal ?? date('Y-m-d')) }}" class="form-control @error('tanggal') is-invalid @enderror" required>
        @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-4">
        <label>Customer</label>
        <select name="company_profile_id" class="form-control @error('company_profile_id') is-invalid @enderror" required>
            <option value="">-- Pilih Customer --</option>
            @foreach($customers as $c)
            <option value="{{ $c->id }}" {{ old('company_profile_id', $invoice->company_profile_id ?? '') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>
        @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-3">
        <label>Sales Group</label>
        <select name="sales_group_id" class="form-control">
            <option value="">-- Pilih Sales Group --</option>
            @foreach($salesGroups as $g)
            <option value="{{ $g->id }}" {{ old('sales_group_id', $invoice->sales_group_id ?? '') == $g->id ? 'selected' : '' }}>
                {{ $g->nama }}
            </option>
            @endforeach
        </select>
    </div>
</div>

<div class="row mb-3">
    <div class="col-md-2">
        <label>Term</label>
        <input name="term" class="form-control" value="{{ old('term', $invoice->term ?? '') }}">
    </div>
    <div class="col-md-2">
        <label>Status</label>
        <select name="is_tunai" class="form-control">
            <option value="1" {{ old('is_tunai', $invoice->is_tunai ?? 0) == 1 ? 'selected' : '' }}>Tunai</option>
            <option value="0" {{ old('is_tunai', $invoice->is_tunai ?? 0) == 0 ? 'selected' : '' }}>Non Tunai</option>
        </select>
    </div>
    <div class="col-md-2">
        <label>No PO</label>
        <input name="no_po" class="form-control" value="{{ old('no_po', $invoice->no_po ?? '') }}">
    </div>
    <div class="col-md-3">
        <label>Jatuh Tempo</label>
        <input name="jatuh_tempo" type="date" class="form-control" value="{{ old('jatuh_tempo', $invoice->jatuh_tempo ?? '') }}">
    </div>
    <div class="col-md-3">
        <label>Catatan</label>
        <input name="catatan" class="form-control" value="{{ old('catatan', $invoice->catatan ?? '') }}">
    </div>
</div>


@include('sales.invoices.partials.items', ['products' => $products, 'branches' => $branches, 'invoice' => $invoice])

@include('sales.invoices.partials.summary', ['invoice' => $invoice])

@push('js')
<script>
    function toggleKodeFaktur() {
        if ($('#auto_kode').is(':checked')) {
            $('#input-kode').prop('readonly', true).val('(auto)');
        } else {
            $('#input-kode').prop('readonly', false);
            // Jika sebelumnya '(auto)', hapus
            if ($('#input-kode').val() === '(auto)') {
                $('#input-kode').val('');
            }
        }
    }

    $(function() {
        // Trigger saat load dan ketika checkbox berubah
        toggleKodeFaktur();
        $('#auto_kode').on('change', toggleKodeFaktur);
    });

    function updateSummary() {
        let subtotal = 0;
        let totalDiskonItem = 0;
        let subtotalSebelumPPN = 0;
        let totalPPN = 0;
        let grandTotal = 0;

        // Loop setiap item-row
        $('#items-wrapper .item-row').each(function() {
            let $row = $(this);
            subtotal += parseFloat($row.find('.sub-total-sblm-disc').val()) || 0;
            totalDiskonItem += parseFloat($row.find('.total-diskon-item-input').val()) || 0;
            subtotalSebelumPPN += parseFloat($row.find('.sub-total-sebelum-ppn-input').val()) || 0;
            grandTotal += parseFloat($row.find('.sub-total-setelah-disc-input').val()) || 0;
            // Jika ingin hitung total PPN saja:
            let ppnPerItem = parseFloat($row.find('.sub-total-setelah-disc-input').val() || 0) - parseFloat($row.find('.sub-total-sebelum-ppn-input').val() || 0);
            totalPPN += ppnPerItem;
        });

        // Ambil diskon faktur & PPN tambahan (jika diisi user)
        let diskonFaktur = parseFloat($('[name="diskon_faktur"]').val()) || 0;
        let diskonPPN = parseFloat($('[name="diskon_ppn"]').val()) || 0;

        // Hitung grand total setelah diskon faktur dan diskon ppn (jika ada)
        let grandTotalWithDiskon = grandTotal - (grandTotal * (diskonFaktur / 100)) + (grandTotal * (diskonPPN / 100));

        // Hitung total bayar (default = grandTotalWithDiskon)
        let sisaTagihan = grandTotalWithDiskon

        // Set value ke summary
        $('[name="subtotal"]').val(subtotal.toFixed(2));
        $('[name="diskon_item"]').val(totalDiskonItem.toFixed(2));
        $('[name="subtotal_sebelum_ppn"]').val(subtotalSebelumPPN.toFixed(2));
        $('[name="grand_total"]').val(grandTotalWithDiskon.toFixed(2));
        $('[name="total_bayar"]').val(0);
        $('[name="sisa_tagihan"]').val(sisaTagihan.toFixed(2));
    }

    // Trigger summary update setiap ada perubahan di item atau summary field
    $('#items-wrapper').on('input keyup change', 'input, select', updateSummary);
    $('[name="diskon_faktur"], [name="diskon_ppn"], [name="total_bayar"]').on('input keyup change', updateSummary);

    // Panggil pertama kali saat page ready
    updateSummary();
</script>
@endpush