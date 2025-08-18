@php
$items = old('items', isset($receipt) ? $receipt->receiptItems->toArray() : []);
@endphp

<div class="mb-2">
    <button type="button" class="btn btn-success" id="btn-select-faktur">
        <i class="fa fa-search"></i> Tarik Faktur
    </button>
</div>

<table class="table table-bordered" id="selected-faktur-table">
    <thead class="bg-light">
        <tr>
            <th>No Faktur</th>
            <th>Tanggal</th>
            <th>Jatuh Tempo</th>
            <th>Nilai Faktur</th>
            <th>Nilai Retur</th>
            <th>Sisa Tagihan</th>
            <th>Catatan</th>
            <th></th>
        </tr>
    </thead>
    <tbody>
        {{-- Filled by JS --}}
    </tbody>
    <!-- <tfoot>
        <tr>
            <td colspan="5" class="text-end"><b>Total Diterima</b></td>
            <td colspan="3" class="fw-bold total-diterima" style="font-size:1.2em">Rp 0</td>
        </tr>
    </tfoot> -->
</table>

<!-- Modal Faktur -->
<div class="modal fade" id="modal-faktur" tabindex="-1" aria-labelledby="modalFakturLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Faktur Penjualan</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-2">
                <table class="table table-bordered table-hover table-sm" id="table-faktur-modal">
                    <thead class="table-light">
                        <tr>
                            <th><input type="checkbox" id="check-all-faktur"></th>
                            <th>No Faktur</th>
                            <th>Tanggal</th>
                            <th>Jatuh Tempo</th>
                            <th>Grand Total</th>
                            <th>Total Retur</th>
                            <th>Sisa Tagihan</th>
                        </tr>
                    </thead>
                    <tbody>
                        {{-- Faktur loaded via JS --}}
                    </tbody>
                </table>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-add-faktur" class="btn btn-primary" data-bs-dismiss="modal">
                    <i class="fa fa-plus"></i> Tambahkan Faktur Terpilih
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')
{{-- Bootstrap JS --}}
<script src="{{ asset('vendor/adminlte/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
let fakturList = [];

// Render faktur yang dipilih ke tabel utama
function renderSelectedFaktur() {
    let $tbody = $('#selected-faktur-table tbody');
    $tbody.empty();
    fakturList.forEach((item, idx) => {
        $tbody.append(`
            <tr>
                <td>
                    <input type="hidden" name="items[${idx}][sales_invoice_id]" value="${item.id}">
                    <input type="hidden" name="items[${idx}][kode]" value="${item.kode}">
                    ${item.kode}
                </td>
                <td>    
                    <input type="hidden" name="items[${idx}][tanggal]" value="${item.tanggal}">
                    ${item.tanggal}
                </td>
                <td>
                    <input type="hidden" name="items[${idx}][jatuh_tempo]" value="${item.jatuh_tempo}">
                    ${item.jatuh_tempo}
                </td>
                <td>
                    <input type="hidden" class="grand-total" name="items[${idx}][total_faktur]" value="${item.total_faktur}">
                    ${Number(item.total_faktur).toLocaleString('id-ID')}
                </td>
                <td>
                    <input type="hidden" class="total-retur" name="items[${idx}][total_retur]" value="${item.total_retur}">
                    ${Number(item.total_retur).toLocaleString('id-ID')}
                </td>
                <td>
                    <input type="hidden" class="sisa-tagihan" name="items[${idx}][sisa_tagihan]" value="${item.sisa_tagihan}">
                    ${Number(item.sisa_tagihan).toLocaleString('id-ID')}
                </td>
                <td>
                    <input name="items[${idx}][catatan]" class="form-control" value="">
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-remove-row"><i class="fa fa-trash"></i></button>
                </td>
            </tr>
        `);
    });
    updateTotal();
}

// Update total diterima di footer
function updateTotal() {
    let total = 0;
    let totalRetur = 0;
    $('.grand-total').each(function(){
        total += parseFloat($(this).val()) || 0;
    });

    $('.total-retur').each(function(){
        totalRetur += parseFloat($(this).val()) || 0;
    });

    // Update total diterima di footer

    $('.total-diterima').text(Number(total - totalRetur).toLocaleString('id-ID'));
}

$(function() {
    // Handler untuk tombol pilih faktur
    $('#btn-select-faktur').on('click', function() {
        let customerId = $('#select-customer').val();
        if (!customerId) {
            alert('Pilih customer terlebih dahulu!');
            return;
        }
        // Load faktur via AJAX
        $.get("{{ url('admin/sales/receipts/tarik-faktur-options') }}?customer_id=" + customerId, function(res) {
            let rows = '';
            res.invoices.forEach(inv => {
                rows += `<tr>
                    <td><input type="checkbox" class="faktur-checkbox" value="${inv.id}" 
                        data-kode="${inv.kode}"
                        data-tanggal="${inv.tanggal}"
                        data-jatuh_tempo="${inv.jatuh_tempo ?? ''}"
                        data-total_faktur="${inv.grand_total}"
                        data-sisa_tagihan="${inv.sisa_tagihan}"
                        data-total_retur="${inv.total_retur}"></td>
                        ></td>
                    <td>${inv.kode}</td>
                    <td>${inv.tanggal}</td>
                    <td>${inv.jatuh_tempo ?? '-'}</td>
                    <td>${Number(inv.grand_total).toLocaleString('id-ID')}</td>
                    <td>${Number(inv.total_retur).toLocaleString('id-ID')}</td>
                    <td>${Number(inv.sisa_tagihan).toLocaleString('id-ID')}</td>
                </tr>`;
            });
            $('#table-faktur-modal tbody').html(rows);
            // Setelah data siap, buka modal manual
            let modal = new bootstrap.Modal(document.getElementById('modal-faktur'));
            modal.show();
        });
    });

    // Select all checkboxes in modal
    $('#table-faktur-modal').on('change', '#check-all-faktur', function(){
        $('#table-faktur-modal .faktur-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Tambah faktur yang terpilih ke list utama
    $('#btn-add-faktur').on('click', function() {
        $('#table-faktur-modal .faktur-checkbox:checked').each(function() {
            let id = $(this).val();
            if (!fakturList.find(x => x.id == id)) {
                fakturList.push({
                    id: id,
                    kode: $(this).data('kode'),
                    tanggal: $(this).data('tanggal'),
                    jatuh_tempo: $(this).data('jatuh_tempo'),
                    total_faktur: $(this).data('total_faktur'),
                    sisa_tagihan: $(this).data('sisa_tagihan'),
                    total_retur: $(this).data('total_retur')
                });
            }
        });
        renderSelectedFaktur();
    });

    // Hapus row dari tabel utama
    $('#selected-faktur-table').on('click', '.btn-remove-row', function() {
        let idx = $(this).closest('tr').index();
        fakturList.splice(idx, 1);
        renderSelectedFaktur();
    });

    // Update total diterima saat input berubah
    $('#selected-faktur-table').on('input', '.diterima-input', updateTotal);

    // Jika ada data lama (old), inisialisasi fakturList
    @php
        $fakturListJs = [];
        if (count($items)) {
            $fakturListJs = collect($items)->map(function($i){
                
                return [
                    'id' => $i['sales_invoice_id'] ?? '',
                    'kode' => $i['invoice']['kode'] ?? '',
                    'tanggal' => $i['invoice']['tanggal'] ?? '',
                    'jatuh_tempo' => $i['invoice']['jatuh_tempo'] ?? '',
                    'total_faktur' => $i['total_faktur'] ?? 0,
                    'sisa_tagihan' => $i['sisa_tagihan'] ?? 0,
                    'total_retur' => $i['total_retur'] ?? 0,
                ];
            });
        }
    @endphp
    @if(count($items) )
    fakturList = @json($fakturListJs);
    renderSelectedFaktur();
    @endif
});
</script>
@endpush
