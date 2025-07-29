@php
$items = old('items', isset($payment) ? $payment->items->toArray() : []);
@endphp

<div class="row mb-3">
    <div class="col-md-4">
        <label>Kode</label>
        <div class="input-group">
            <input
                name="kode"
                id="input-kode"
                class="form-control @error('kode') is-invalid @enderror"
                value="{{ old('kode', $payment->kode ?? '') ?: '(auto)' }}"
                @if(old('auto_kode', is_null(old('kode', $payment->kode ?? null)) || old('kode', $payment->kode ?? '') === '' ? 1 : 0)) readonly @endif
            autocomplete="off"
            @if($payment && $payment->kode) disabled @endif

            >
            <div class="input-group-text bg-light">
                <input
                    type="checkbox"
                    id="auto_kode"
                    name="auto_kode"
                    value="1"
                    class="form-check-input mt-0"
                    {{ old('auto_kode', (is_null(old('kode', $payment->kode ?? null)) || old('kode', $payment->kode ?? '') === '') ? 1 : 0) ? 'checked' : '' }}
                    @if($payment && $payment->kode) disabled @endif
                >
                <label for="auto_kode" class="mb-0 ms-1" style="font-size: 0.93em; cursor:pointer;">Auto</label>
            </div>
        </div>
        @error('kode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label>Tanggal</label>
        <input type="date" name="tanggal" class="form-control" value="{{ old('tanggal', $payment->tanggal ?? date('Y-m-d')) }}" required>
    </div>
    <div class="col-md-4">
        <label>Supplier</label>
        <select name="company_profile_id" id="select-supplier" class="form-control" required>
            <option value="">-- Pilih Supplier --</option>
            @foreach($suppliers as $s)
            <option value="{{ $s->id }}" {{ old('company_profile_id', $payment->company_profile_id ?? '') == $s->id ? 'selected' : '' }}>
                {{ $s->name }}
            </option>
            @endforeach
        </select>
        @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
    </div>
    <div class="col-md-2">
        <label>Catatan</label>
        <input type="text" name="catatan" class="form-control" value="{{ old('catatan', $payment->catatan ?? '') }}">
    </div>
</div>

@include('purchases.payments.partials.items', ['items' => $items])


@push('js')
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
    // let notaList = [];

    // function renderSelectedNota() {
    //     let $tbody = $('#selected-nota-table tbody');
    //     $tbody.empty();
    //     notaList.forEach((item, idx) => {
    //         $tbody.append(`
    //         <tr>
    //             <td>
    //                 <input type="hidden" name="items[${idx}][tipe_nota]" value="${item.tipe}">
    //                 <input type="hidden" name="items[${idx}][purchases_invoice_id]" value="${item.tipe == 'FAKTUR' ? item.id : ''}">
    //                 <input type="hidden" name="items[${idx}][purchases_return_id]" value="${item.tipe == 'RETUR' ? item.id : ''}">
    //                 ${item.tipe}
    //             </td>
    //             <td>${item.kode}</td>
    //             <td>${item.tanggal}</td>
    //             <td><input type="text" class="form-control" value="${item.nilai_nota}" readonly></td>
    //             <td><input type="text" class="form-control" value="${item.sisa}" readonly></td>
    //             <td><input name="items[${idx}][kas]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][bank]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][giro]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][cndn]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][retur]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][panjar]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][lainnya]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][sub_total]" type="number" step="0.01" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][pot_ke_no]" class="form-control" value=""></td>
    //             <td><input name="items[${idx}][catatan]" class="form-control" value=""></td>
    //             <td>
    //                 <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
    //             </td>
    //         </tr>
    //     `);
    //     });
    // }

    $(function() {
        $('#btn-select-nota').on('click', function() {
            let supplierId = $('#select-supplier').val();
            if (!supplierId) {
                alert('Pilih supplier terlebih dahulu!');
                return false;
            }
            // Load faktur & retur
            $.get("{{ url('admin/purchases/payments/tarik-nota-options') }}?company_profile_id=" + supplierId, function(res) {
                let rows = '';
                console.log(res);
                res.invoices.forEach(inv => {
                    rows += `<tr>
                    <td><input type="checkbox" class="nota-checkbox" value="${inv.id}" data-tipe-nota="FAKTUR" data-kode="${inv.kode}" data-tanggal="${inv.tanggal}" data-nilai="${inv.grand_total}" data-sisa="${inv.sisa_tagihan}"></td>
                    <td>FAKTUR</td>
                    <td>${inv.kode}</td>
                    <td>${inv.tanggal}</td>
                    <td>${inv.grand_total}</td>
                    <td>${inv.sisa_tagihan}</td>
                </tr>`;
                });
                // res.returns.forEach(ret => {
                //     rows += `<tr>
                //     <td><input type="checkbox" class="nota-checkbox" value="${ret.id}" data-tipe-nota="RETUR" data-kode="${ret.kode}" data-tanggal="${ret.tanggal}" data-nilai="${ret.grand_total}" data-sisa="${ret.grand_total}"></td>
                //     <td>RETUR</td>
                //     <td>${ret.kode}</td>
                //     <td>${ret.tanggal}</td>
                //     <td>${ret.grand_total}</td>
                //     <td>${ret.grand_total}</td>
                // </tr>`;
                // });
                $('#table-nota-modal tbody').html(rows);
            });
        });

        // Add selected nota to list
        $('#btn-add-nota').on('click', function() {
            $('#table-nota-modal .nota-checkbox:checked').each(function() {
                let id = $(this).val();
                let tipeNota = $(this).data('tipe-nota');
                if (!notaList.find(x => x.id == id && x.tipe == tipe)) {
                    notaList.push({
                        id: id,
                        tipe_nota: tipeNota,
                        kode: $(this).data('kode'),
                        tanggal: $(this).data('tanggal'),
                        nilai_nota: $(this).data('nilai'),
                        sisa: $(this).data('sisa')
                    });
                }
            });
            renderSelectedNota();
        });

        // Remove
        $('#selected-nota-table').on('click', '.btn-remove-row', function() {
            let idx = $(this).closest('tr').index();
            notaList.splice(idx, 1);
            renderSelectedNota();
        });

        // Recalculate SUBTOTAL when any value changes
        $('#selected-nota-table').on('input', 'input[type=number]', function() {
            let $tr = $(this).closest('tr');
            let idx = $tr.index();
            let tunai = parseFloat($tr.find('input[name^="items["][name$="[tunai]"]').val()) || 0;
            let bank = parseFloat($tr.find('input[name^="items["][name$="[bank]"]').val()) || 0;
            let giro = parseFloat($tr.find('input[name^="items["][name$="[giro]"]').val()) || 0;
            let cndn = parseFloat($tr.find('input[name^="items["][name$="[cndn]"]').val()) || 0;
            let retur = parseFloat($tr.find('input[name^="items["][name$="[retur]"]').val()) || 0;
            let panjar = parseFloat($tr.find('input[name^="items["][name$="[panjar]"]').val()) || 0;
            let lainnya = parseFloat($tr.find('input[name^="items["][name$="[lainnya]"]').val()) || 0;
            let sub_total = kas + bank + giro + cndn + retur + panjar + lainnya;
            $tr.find('input[name^="items["][name$="[sub_total]"]').val(sub_total.toFixed(2));
        });

        // Auto kode handling
        function toggleKode() {
            if ($('#auto_kode').is(':checked')) {
                $('#input-kode').prop('readonly', true).val('(auto)');
            } else {
                $('#input-kode').prop('readonly', false);
                if ($('#input-kode').val() === '(auto)') {
                    $('#input-kode').val('');
                }
            }
        }
        $('#auto_kode').on('change', toggleKode);
        toggleKode();

        // If you want to init data from old/validation error, adapt here...

    });
</script>
@endpush