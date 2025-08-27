<div class="mb-2">
    <button type="button" class="btn btn-success" id="btn-select-nota" data-bs-toggle="modal" data-bs-target="#modal-nota">
        <i class="fa fa-search"></i> Tarik Nota
    </button>
</div>

<div id="selected-nota-list">
    {{-- Diisi via JS --}}
</div>

<!-- Modal Nota (same as before, unchanged) -->
<div class="modal fade" id="modal-nota" tabindex="-1" aria-labelledby="modalNotaLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Pilih Nota Pembelian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle" id="table-nota-modal">
                        <thead class="table-light">
                            <tr>
                                <th></th>
                                <th>Tipe</th>
                                <th>No Nota</th>
                                <th>Tanggal</th>
                                <th>Nilai Nota</th>
                                <th>Sisa</th>
                            </tr>
                        </thead>
                        <tbody>
                            {{-- Diisi JS/AJAX --}}
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="btn-add-nota" class="btn btn-primary" data-bs-dismiss="modal">
                    Tambahkan Nota Terpilih
                </button>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    let notaList = [];

    function renderSelectedNota() {
        let $list = $('#selected-nota-list');
        $list.empty();
        notaList.forEach((item, idx) => {
            $list.append(`
            <div class="card mb-3 shadow-sm border border-primary">
                <div class="card-body p-3">
                    <div class="row gy-2 gx-3 align-items-center">
                        <div class="col-md-2">
                            <label class="fw-bold">Tipe</label>
                            <input type="text" class="form-control-plaintext" readonly value="${item.tipe_nota}">
                            <input type="hidden" name="items[${idx}][tipe_nota]" value="${item.tipe_nota}">
                            <input type="hidden" name="items[${idx}][purchases_invoice_id]" value="${item.purchases_invoice_id}">
                            <input type="hidden" name="items[${idx}][id]" value="${item.id}">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">No Nota</label>
                            <input type="text" class="form-control-plaintext" readonly value="${item.kode ?? item.invoice.kode}">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">Tanggal</label>
                            <input type="text" class="form-control-plaintext" readonly value="${item.tanggal ?? item.invoice.tanggal}">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">Nilai Nota</label>
                            <input type="text" class="form-control-plaintext nilai-nota" readonly value="${Number(item.nilai_nota).toLocaleString("id-ID")}">
                            <input type="hidden" name="items[${idx}][nilai_nota]" class="nilai-nota" value="${Number(item.nilai_nota) || 0}">
                        </div>
                        <div class="col-md-2">
                            <label class="fw-bold">Sisa</label>
                            <input id="items[${idx}]_sisa_display" type="text" class="form-control sisa-input-display format-number" readonly value="${ Number(item.sisa || 0).toLocaleString("id-ID")}" autocomplete="off">
                            <input class="sisa-input" type="hidden" id="items[${idx}]_sisa" name="items[${idx}][sisa]" data-sisa-db="${item.sisa ?? 0}"  value="${Number(item.sisa) || 0}" >
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-danger btn-remove-nota mt-3">
                                <i class="fa fa-trash"></i> Hapus
                            </button>
                        </div>
                    </div>
                    <div class="row gy-2 gx-3 mt-2">
                        <div class="col-md-2">
                            <label>Tunai</label>
                            <input type="text" class="form-control bayar-input format-number" min="0" value="${item.tunai ?? 0}" autocomplete="off" id="items[${idx}]_tunai_display">
                            <input type="hidden" class="tunai-input" data-tunai-db="${item.tunai ?? 0}" name="items[${idx}][tunai]" id="items[${idx}]_tunai" value="${Number(item.tunai) || 0}">
                        </div>
                        <div class="col-md-2">
                            <label>BANK</label>
                            <input type="text" class="form-control bayar-input format-number" min="0" data-bank-db="${item.bank ?? 0}" value="${item.bank ?? 0}" autocomplete="off" id="items[${idx}]_bank_display">
                            <input class="bank-input" type="hidden" id="items[${idx}]_bank" value="${Number(item.bank) || 0}" data-bank-db="${item.bank ?? 0}" name="items[${idx}][bank]">
                        </div>
                        <div class="col-md-2">
                            <label>GIRO</label>
                            <input type="text" class="form-control bayar-input format-number" min="0" data-giro-db="${item.giro ?? 0}" value="${item.giro ?? 0}" autocomplete="off" id="items[${idx}]_giro_display">
                            <input class="giro-input" type="hidden" id="items[${idx}]_giro" value="${Number(item.giro) || 0}" data-giro-db="${item.giro ?? 0}" name="items[${idx}][giro]">
                        </div>
                        <div class="col-md-2">
                            <label>CNDN</label>
                            <input type="text" class="form-control bayar-input format-number" min="0" data-cndn-db="${item.cndn ?? 0}" value="${item.cndn ?? 0}" autocomplete="off" id="items[${idx}]_cndn_display">
                            <input class="cndn-input" type="hidden" id="items[${idx}]_cndn" value="${Number(item.cndn) || 0}" data-cndn-db="${item.cndn ?? 0}" name="items[${idx}][cndn]">
                        </div>
                        <div class="col-md-2">
                            <label>RETUR</label>
                            <input type="text" class="form-control retur-input-change format-number" data-retur-db="${item.retur || 0}"  value="${item.retur || 0}" autocomplete="off" id="items[${idx}]_retur_display">
                            <input class="retur-input" type="hidden" id="items[${idx}]_retur" value="${Number(item.retur) || 0}" data-retur-db="${item.retur || 0}" name="items[${idx}][retur]">
                        </div>
                        <div class="col-md-2">
                            <label>PANJAR</label>
                            <input type="text" class="form-control bayar-input format-number" min="0" data-panjar-db="${item.panjar ?? 0}" value="${item.panjar ?? 0}" autocomplete="off" id="items[${idx}]_panjar_display">
                            <input class="panjar-input" type="hidden" id="items[${idx}]_panjar" value="${Number(item.panjar) || 0}" data-panjar-db="${item.panjar ?? 0}" name="items[${idx}][panjar]">
                        </div>
                    </div>
                    <div class="row gy-2 gx-3 mt-2">
                        <div class="col-md-2">
                            <label>LAINNYA</label>
                            <input  type="text" class="form-control bayar-input format-number" min="0" data-lainnya-db="${item.lainnya ?? 0}" value="${item.lainnya ?? 0}" autocomplete="off" id="items[${idx}]_lainnya_display">
                            <input class="lainnya-input" type="hidden" id="items[${idx}]_lainnya" value="${Number(item.lainnya) || 0}" data-lainnya-db="${item.lainnya ?? 0}" name="items[${idx}][lainnya]">
                        </div>
                        <div class="col-md-2">
                            <label>SUBTOTAL</label>
                             <input type="text" class="form-control format-number subtotal-input-display" data-subtotal-db="${item.sub_total ?? 0}" readonly value="${item.sub_total ?? 0}" autocomplete="off" id="items[${idx}]_subtotal_display">
                            <input class="subtotal-input" type="hidden" id="items[${idx}]_subtotal" value="${Number(item.sub_total) ?? 0}" data-subtotal-db="${item.sub_total ?? 0}" name="items[${idx}][sub_total]">
                        </div>
                          <div class="col-md-2">
                            <label>RETUR di Faktur</label>
                           <input type="text" " class="retur-faktur-input form-control" value="${Number(item.retur) !== 0 ? Number(item.retur || 0).toLocaleString("id-ID") : Number(item.invoice.total_retur || 0).toLocaleString("id-ID")}" readonly autocomplete="off">
                        </div>
                        <div class="col-md-5">
                            <label>Catatan</label>
                            <input name="items[${idx}][catatan]" type="text" class="form-control catatan-input" value="${item.catatan ?? ''}">
                        </div>
                    </div>
                </div>
            </div>
        `);
        });

        // After render, recalculate all subtotal/sisa
        // $('#selected-nota-list .card').each(function(idx) {
        //     calcSubtotalAndSisa(idx);
        // });
    }

    function calcSubtotalAndSisa(idx) {
        let $card = $('#selected-nota-list .card').eq(idx);

        // Get payment inputs
        let tunai = parseFloat($card.find('.tunai-input').val()) || 0;
        let tunaiDb = parseFloat($card.find('.tunai-input').data('tunai-db')) || 0;
        let bank = parseFloat($card.find('.bank-input').val()) || 0;
        let bankDb = parseFloat($card.find('.bank-input').data('bank-db')) || 0;
        let giro = parseFloat($card.find('.giro-input').val()) || 0;
        let giroDb = parseFloat($card.find('.giro-input').data('giro-db')) || 0;
        let cndn = parseFloat($card.find('.cndn-input').val()) || 0;
        let cndnDb = parseFloat($card.find('.cndn-input').data('cndn-db')) || 0;
        let panjar = parseFloat($card.find('.panjar-input').val()) || 0;
        let panjarDb = parseFloat($card.find('.panjar-input').data('panjar-db')) || 0;
        let lainnya = parseFloat($card.find('.lainnya-input').val()) || 0;
        let lainnyaDb = parseFloat($card.find('.lainnya-input').data('lainnya-db')) || 0;
        let retur = parseFloat($card.find('.retur-input').val()) || 0;
        let returDb = parseFloat($card.find('.retur-input').data('retur-db')) || 0;
        let nilaiNota = parseFloat($card.find('.nilai-nota').val()) || 0;

        let sisaOld = parseFloat($card.find('.sisa-input').data('sisa-db')) || 0;

        // Get sisa from the card

        // Calculate subtotal and sisa
        let subtotal = tunai + bank + giro + cndn + panjar + lainnya + retur;   
        let subtotalDb = tunaiDb + bankDb + giroDb + cndnDb + panjarDb + lainnyaDb + returDb;
        let sisa = sisaOld - (subtotal - subtotalDb);

        $card.find('.subtotal-input').val(subtotal);
        $card.find('.subtotal-input-display').val(Number(subtotal).toLocaleString("id-ID"));
        $card.find('.sisa-input').val(sisa);
        $card.find('.sisa-input-display').val(Number(sisa).toLocaleString("id-ID"));
    }

    $(function() {
        // (modal fetch, add, and remove logic same as previous version...)
        // Replace all '#selected-nota-table' code with #selected-nota-list and use renderSelectedNota() above.

        $('#selected-nota-list').on('input keyup', '.bayar-input', function() {
            let $card = $(this).closest('.card');
            let idx = $card.index();
            calcSubtotalAndSisa(idx);
        });

        $('#selected-nota-list').on('input keyup', '.retur-input-change', function() {
            let $card = $(this).closest('.card');
            let idx = $card.index();
            calcSubtotalAndSisa(idx);
        });
        // TODO: Add other JS as needed (fetch nota, etc)
    });

    // Remove nota from list
    $('#selected-nota-list').on('click', '.btn-remove-nota', function() {
        // Get the card index in the current list
        let idx = $(this).closest('.card').index();

        // Remove from notaList array
        notaList.splice(idx, 1);

        // Re-render the list from the updated array
        renderSelectedNota();
    });

    @if(count($items))
    notaList = @json($items);
    renderSelectedNota();
    @endif
</script>
@endpush