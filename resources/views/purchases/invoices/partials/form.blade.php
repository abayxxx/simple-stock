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
        <label>Supplier</label>
        <select name="company_profile_id" class="form-control @error('company_profile_id') is-invalid @enderror" required>
            <option value="">-- Pilih Supplier --</option>
            @foreach($suppliers as $c)
            <option value="{{ $c->id }}" {{ old('company_profile_id', $invoice->company_profile_id ?? '') == $c->id ? 'selected' : '' }}>
                {{ $c->name }}
            </option>
            @endforeach
        </select>
        @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label>Term</label>
        <input name="term" class="form-control" value="{{ old('term', $invoice->term ?? '') }}">
    </div>
</div>

<div class="row mb-3">



    <div class="col-md-2">
        <label>No Order</label>
        <input name="no_order" class="form-control" value="{{ old('no_order', $invoice->no_order ?? '') }}">
    </div>
    <!-- <div class="col-md-3">
        <label>Jatuh Tempo</label>
        <input name="jatuh_tempo" type="date" class="form-control" value="{{ old('jatuh_tempo', $invoice->jatuh_tempo ?? '') }}">
    </div> -->
    <div class="col-md-3">
        <label>Catatan</label>
        <input name="catatan" class="form-control" value="{{ old('catatan', $invoice->catatan ?? '') }}">
    </div>

    <div class=" col-md-2 d-flex align-items-center">
        <div class="form-check mt-2">
            <input type="hidden" name="is_tunai" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="is_tunai"
                name="is_tunai"
                value="1"
                {{ old('is_tunai', $invoice->is_tunai ?? 0) == 1 ? 'checked' : '' }}>
            <label for="is_tunai" class="form-check-label">Tunai</label>
        </div>
    </div>
    <div class=" col-md-2 d-flex align-items-center">
        <div class="form-check mt-2">
            <input type="hidden" name="is_include_ppn" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="is_include_ppn"
                name="is_include_ppn"
                value="1"
                {{ old('is_include_ppn', $invoice->is_include_ppn ?? 0) == 1 ? 'checked' : '' }}>
            <label for="is_include_ppn" class="form-check-label">Inc. PPN</label>
        </div>
    </div>
    <div class=" col-md-2 d-flex align-items-center">
        <div class="form-check mt-2 ">
            <input type="hidden" name="is_received" value="0">
            <input
                type="checkbox"
                class="form-check-input"
                id="is_received"
                name="is_received"
                value="1"
                {{ old('is_received', $invoice->is_received ?? 0) == 1 ? 'checked' : '' }}>
            <label for="is_received" class="form-check-label">Sudah Diterima</label>
        </div>
    </div>
</div>



@include('purchases.invoices.partials.items', ['products' => $products, 'branches' => $branches, 'invoice' => $invoice])

@include('purchases.invoices.partials.summary', ['invoice' => $invoice])

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

    // Loop setiap item di tabel review
    $('#review-items-table tbody tr').each(function() {
        // Ambil index/data-index di row
        let idx = $(this).data('index');
        let prefix = `items[${idx}]`;

        // Ambil nilai dari input hidden
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
</script>
@endpush