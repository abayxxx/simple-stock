<!-- Modal History Penjualan -->
<div class="modal fade" id="modal-history" tabindex="-1" aria-labelledby="modalHistoryLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title" id="modalHistoryLabel">Daftar Historis Faktur Penjualan</h5>
        <button type="button" class="btn-close" data-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-2">
        <div class="table-responsive">
          <table class="table table-bordered table-striped table-hover" id="table-history">
            <thead class="table-light">
              <tr>
                <th>NO.</th>
                <th>TANGGAL</th>
                <th>NAMA</th>
                <th>NAMA PRODUK</th>
                <th>QTY.</th>
                <th>HARGA (KECIL)</th>
                <th>DISC 1 (%)</th>
                <th>DISC 1 (RP)</th>
                <th>DISC 2 (%)</th>
                <th>DISC 2 (RP)</th>
                <th>DISC 3 (%)</th>
                <th>DISC 3 (RP)</th>
                <th>SUB TOTAL (SEBELUM DISC)</th>
                <th>TOTAL DISC. ITEM</th>
                <th>SUB TOTAL (SEBELUM PPN)</th>
                <th>PPN (%)</th>
                <th>SUB TOTAL (SETELAH DISC)</th>
                <th>CATATAN</th>
              </tr>
            </thead>
            <tbody>
              {{-- Diisi via JS --}}
            </tbody>
          </table>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-danger btn-sm" data-dismiss="modal"><i class="fa fa-times"></i> Tutup [ESC]</button>
      </div>
    </div>
  </div>
</div>
