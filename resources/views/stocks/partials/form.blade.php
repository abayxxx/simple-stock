@php $stock = $stock ?? null; @endphp

<div class="form-group mb-3">
    <label>Produk <span class="text-danger">*</span></label>
    <select name="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
        <option value="">-- Pilih Produk --</option>
        @foreach($products as $p)
        <option value="{{ $p->id }}" {{ old('product_id', $stock->product_id ?? '') == $p->id ? 'selected' : '' }}>
            {{ $p->nama }} ({{ $p->kode }})
        </option>
        @endforeach
    </select>
    @error('product_id')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>No. Seri</label>
    <input name="no_seri" value="{{ old('no_seri', $stock->no_seri ?? '') }}" class="form-control @error('no_seri') is-invalid @enderror">
    @error('no_seri')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Tanggal Expired</label>
    <input name="tanggal_expired" type="date" value="{{ old('tanggal_expired', $stock->tanggal_expired ?? '') }}" class="form-control @error('tanggal_expired') is-invalid @enderror">
    @error('tanggal_expired')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Qty. Unit</label>
    <input name="jumlah" type="number" min="1" value="{{ old('jumlah', $stock->jumlah ?? 1) }}"
           class="form-control @error('jumlah') is-invalid @enderror" required>
    @error('jumlah')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Harga Pokok Net.</label>
    <input name="harga_net" type="number" step="0.01" value="{{ old('harga_net', $stock->harga_net ?? 0) }}"
           class="form-control @error('harga_net') is-invalid @enderror" required>
    @error('harga_net')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Sub Total</label>
    <input name="subtotal" type="number" step="0.01" value="{{ old('subtotal', $stock->subtotal ?? 0) }}"
           class="form-control @error('subtotal') is-invalid @enderror" required readonly>
    @error('subtotal')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Catatan</label>
    <textarea name="catatan" class="form-control @error('catatan') is-invalid @enderror">{{ old('catatan', $stock->catatan ?? '') }}</textarea>
    @error('catatan')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>

<div class="form-group mb-3">
    <label>Sisa Stok</label>
    <input id="sisa_stok" class="form-control" value="{{ old('sisa_stok', $stock->sisa_stok ?? 0) }}" readonly>
</div>

{{-- OPTIONAL: Error global di atas form --}}
@if ($errors->any())
    <div class="alert alert-danger">
        @foreach ($errors->all() as $err)
            <div>{{ $err }}</div>
        @endforeach
    </div>
@endif

@push('js')
<script>
    $(function() {
        let type = "{{ $type ?? 'in' }}";

        function hitungSubtotal() {
            let qty = parseFloat($('[name="jumlah"]').val()) || 0;
            let harga = parseFloat($('[name="harga_net"]').val()) || 0;
            let subtotal = qty * harga;
            $('[name="subtotal"]').val(subtotal.toFixed(2));
        }

        function updateSisaStok() {
            let productId = $('[name="product_id"]').val();
            let qty = parseFloat($('[name="jumlah"]').val()) || 0;
            if(productId) {
                $.get("{{ route('stock.get_sisa_stok', ':id') }}".replace(':id', productId), function(res) {
                    let stokSekarang = Number(res);
                    let sisa;
                    if(type === 'in') {
                        sisa = stokSekarang + qty;
                    } else if(type === 'out' || type === 'destroy') {
                        sisa = stokSekarang - qty;
                    } else {
                        sisa = stokSekarang;
                    }
                    $('#sisa_stok').val(sisa);
                });
            } else {
                $('#sisa_stok').val('0');
            }
        }

        // Trigger otomatis
        $('[name="product_id"]').on('change', updateSisaStok);
        $('[name="jumlah"]').on('input', updateSisaStok);
        $('[name="jumlah"], [name="harga_net"]').on('input', hitungSubtotal);

        // Trigger pertama kali jika edit
        @php
            $shouldTrigger = old('product_id', $stock->product_id ?? false) ? 'true' : 'false';
        @endphp
        if (@json($shouldTrigger)) {
            updateSisaStok();
        }
    });
</script>
@endpush
