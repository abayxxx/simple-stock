@php
$profile = $profile ?? null;
$external = $profile && $profile->externalData ? $profile->externalData : null;
@endphp

{{-- Data Profil --}}
@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<div class="row">
    <div class="col-md-6">
        <div class="form-group">
            <label>Nama <span class="text-danger">*</span></label>
            <input name="name" value="{{ old('name', $profile->name ?? '') }}" class="form-control" required>
            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Alamat</label>
            <input name="address" value="{{ old('address', $profile->address ?? '') }}" class="form-control">
            @error('address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Lokasi Spesifik</label>
            <input name="spesific_location" value="{{ old('spesific_location', $profile->spesific_location ?? '') }}" class="form-control">
            @error('spesific_location') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Telepon</label>
            <input name="phone" value="{{ old('phone', $profile->phone ?? '') }}" class="form-control">
            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Email</label>
            <input name="email" value="{{ old('email', $profile->email ?? '') }}" class="form-control" type="email">
            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Website</label>
            <input name="website" value="{{ old('website', $profile->website ?? '') }}" class="form-control" type="text">
            @error('website') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Relasi <span class="text-danger">*</span></label>
            <select name="relationship" class="form-control" required>
                <option value="">Pilih</option>
                <option value="customer" {{ old('relationship', $profile->relationship ?? '') == 'customer' ? 'selected' : '' }}>Pelanggan</option>
                <option value="supplier" {{ old('relationship', $profile->relationship ?? '') == 'supplier' ? 'selected' : '' }}>Supplier</option>
                <option value="other" {{ old('relationship', $profile->relationship ?? '') == 'other' ? 'selected' : '' }}>Lainnya</option>
            </select>
            @error('relationship') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>NPWP</label>
            <input name="npwp" value="{{ old('npwp', $profile->npwp ?? '') }}" class="form-control">
            @error('npwp') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Nama Faktur Pajak</label>
            <input name="tax_invoice_to" value="{{ old('tax_invoice_to', $profile->tax_invoice_to ?? '') }}" class="form-control">
            @error('tax_invoice_to') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="form-group">
            <label>Alamat Faktur Pajak</label>
            <input name="tax_invoice_address" value="{{ old('tax_invoice_address', $profile->tax_invoice_address ?? '') }}" class="form-control">
            @error('tax_invoice_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>

    {{-- Data Eksternal --}}
    <div class="col-md-6">
        <h5>Data Eksternal</h5>
        @php
        $fields = [
        'total_receivable_now' => 'Total Piutang Saat Ini',
        'unpaid_sales_invoices_count' => 'Jlh. Faktur Penjualan Belum Lunas',
        'last_sales_date' => 'Tgl. Penjualan Terakhir',
        'giro_received' => 'Giro Terima',
        'due_receivables' => 'Piutang Jatuh Tempo',
        'due_sales_invoices_count' => 'Jlh. Faktur Penjualan Jatuh Tempo',
        'grand_total_sales' => 'Grand Total Penjualan',
        'grand_total_sales_returns' => 'Grand Total Retur Penjualan',
        'total_debt_now' => 'Total Hutang Saat Ini',
        'unpaid_purchase_invoices_count' => 'Jlh. Faktur Pembelian Belum Lunas',
        'last_purchase_date' => 'Tgl. Pembelian Terakhir',
        'giro_paid' => 'Giro Bayar',
        'due_debt' => 'Hutang Jatuh Tempo',
        'due_purchase_invoices_count' => 'Jlh. Faktur Pembelian Jatuh Tempo',
        'grand_total_purchases' => 'Grand Total Pembelian',
        'grand_total_purchase_returns' => 'Grand Total Retur Pembelian'
        ];
        @endphp
        @foreach($fields as $field => $label)
        <div class="form-group">
            <label>{{ $label }}</label>
            @if(str_contains($field, 'date'))
            <input name="{{ $field }}" value="{{ old($field, $external ? $external->$field : '') }}" class="form-control" type="date">
            @else
            <input name="{{ $field }}" value="{{ old($field, $external ? $external->$field : 0) }}" class="form-control" type="number" step="0.0001">
            @endif
        </div>
        @endforeach
    </div>
</div>