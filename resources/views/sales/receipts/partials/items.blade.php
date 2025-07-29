@php
$items = old('items', isset($receipt) ? $receipt->items->toArray() : [ [] ]);
@endphp
<div id="items-wrapper">
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>No Faktur</th>
                <th>Tagihan</th>
                <th>Diterima (Rp)</th>
                <th>Cash</th>
                <th>Transfer</th>
                <th>Giro</th>
                <th>Bank</th>
                <th>No Giro</th>
                <th>Jth Tempo Giro</th>
                <th>Catatan</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $rowIdx => $item)
            <tr class="item-row">
                <td>
                    <select name="items[{{ $rowIdx }}][sales_invoice_id]" class="form-control select-invoice" required>
                        <option value="">-- Pilih Faktur --</option>
                        @foreach($availableInvoices as $inv)
                        <option value="{{ $inv->id }}"
                            {{ old("items.$rowIdx.sales_invoice_id", $item['sales_invoice_id'] ?? '') == $inv->id ? 'selected' : '' }}>
                            {{ $inv->kode }} - {{ $inv->tanggal }} - Tagihan: {{ number_format($inv->sisa_tagihan, 2, ',', '.') }}
                        </option>
                        @endforeach
                    </select>
                </td>
                <td>
                    <input type="text" class="form-control tagihan-input" readonly value="{{ $item['tagihan'] ?? '' }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][jumlah_diterima]" type="number" class="form-control diterima-input" min="0" value="{{ old("items.$rowIdx.jumlah_diterima", $item['jumlah_diterima'] ?? 0) }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][jumlah_cash]" type="number" class="form-control" min="0" value="{{ old("items.$rowIdx.jumlah_cash", $item['jumlah_cash'] ?? 0) }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][jumlah_transfer]" type="number" class="form-control" min="0" value="{{ old("items.$rowIdx.jumlah_transfer", $item['jumlah_transfer'] ?? 0) }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][jumlah_giro]" type="number" class="form-control" min="0" value="{{ old("items.$rowIdx.jumlah_giro", $item['jumlah_giro'] ?? 0) }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][bank]" class="form-control" value="{{ old("items.$rowIdx.bank", $item['bank'] ?? '') }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][giro_no]" class="form-control" value="{{ old("items.$rowIdx.giro_no", $item['giro_no'] ?? '') }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][giro_jatuh_tempo]" type="date" class="form-control" value="{{ old("items.$rowIdx.giro_jatuh_tempo", $item['giro_jatuh_tempo'] ?? '') }}">
                </td>
                <td>
                    <input name="items[{{ $rowIdx }}][catatan]" class="form-control" value="{{ old("items.$rowIdx.catatan", $item['catatan'] ?? '') }}">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <button type="button" class="btn btn-success" id="add-item-row"><i class="fa fa-plus"></i> Tambah Faktur</button>
</div>

@push('js')
<script>
$(function() {
    let rowIdx = $('#items-wrapper .item-row').length;

    $('#add-item-row').on('click', function() {
        let $table = $('#items-wrapper tbody');
        let $lastRow = $table.find('.item-row:last');
        let newRow = $lastRow.clone();
        newRow.find('input, select').each(function() {
            let name = $(this).attr('name');
            if (name) {
                name = name.replace(/\[\d+\]/, '[' + rowIdx + ']');
                $(this).attr('name', name).val('');
            }
        });
        $table.append(newRow);
        rowIdx++;
    });

    $('#items-wrapper').on('click', '.btn-remove-row', function() {
        if ($('#items-wrapper .item-row').length > 1)
            $(this).closest('.item-row').remove();
    });

    // Auto-fill tagihan field when invoice selected
    $('#items-wrapper').on('change', '.select-invoice', function() {
        let invoiceId = $(this).val();
        let $row = $(this).closest('tr');
        if (!invoiceId) {
            $row.find('.tagihan-input').val('');
            return;
        }
        $.get("{{ url('admin/sales/invoice-info') }}/" + invoiceId, function(res) {
            $row.find('.tagihan-input').val(res.sisa_tagihan);
        });
    });

    // Calculate Total
    function updateTotal() {
        let totalTagihan = 0, totalDiterima = 0;
        $('#items-wrapper .item-row').each(function() {
            let tagihan = parseFloat($(this).find('.tagihan-input').val()) || 0;
            let diterima = parseFloat($(this).find('.diterima-input').val()) || 0;
            totalTagihan += tagihan;
            totalDiterima += diterima;
        });
        $('.total-tagihan').text(totalTagihan.toLocaleString('id-ID', {style:'currency',currency:'IDR'}));
        $('.total-diterima').text(totalDiterima.toLocaleString('id-ID', {style:'currency',currency:'IDR'}));
    }

    $('#items-wrapper').on('input', '.tagihan-input, .diterima-input', updateTotal);
    updateTotal();
});
</script>
@endpush
