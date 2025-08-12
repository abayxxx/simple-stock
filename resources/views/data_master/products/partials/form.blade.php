@php
$product = $product ?? null;
$satuanList = $satuanList ?? ['BOX', 'PCS', 'BTL', 'UNIT', 'AMP', 'VIAL', 'TUBE'];
$satuanMassaList = $satuanMassaList ?? ['GRAM', 'KG', 'MG', 'LITER', 'ML', 'OUNCE'];
@endphp

@if ($errors->any())
<div class="alert alert-danger">
    <ul>
        @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
        @endforeach
    </ul>
</div>
@endif
<ul class="nav nav-tabs" id="tabProduk" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="umum-tab" data-toggle="tab" href="#umum" role="tab">Data Umum</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="pokok-tab" data-toggle="tab" href="#pokok" role="tab">Harga Pokok</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="jual-tab" data-toggle="tab" href="#jual" role="tab">Harga Jual</a>
    </li>
</ul>
<div class="tab-content mt-3">
    {{-- Data Umum --}}
    <div class="tab-pane fade show active" id="umum" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>Nama Produk <span class="text-danger">*</span></label>
                    <input name="nama" value="{{ old('nama', $product->nama ?? '') }}" class="form-control" required>
                    @error('nama') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group mb-3">
                    <label>Merk</label>
                    <input name="merk" value="{{ old('merk', $product->merk ?? '') }}" class="form-control">
                    @error('merk') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Satuan Kecil <span class="text-danger">*</span></label>
                    <select name="satuan_kecil" class="form-control" required>
                        <option value="">-- Pilih Satuan --</option>
                        @foreach($satuanList as $satuan)
                        <option value="{{ $satuan }}"
                            {{ old('satuan_kecil', $product->satuan_kecil ?? '') == $satuan ? 'selected' : '' }}>
                            {{ $satuan }}
                        </option>
                        @endforeach
                    </select>
                    @error('satuan_kecil') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Isi Satuan Kecil</label>
                    <input name="isi_satuan_kecil" value="{{ old('isi_satuan_kecil', $product->isi_satuan_kecil ?? 1) }}" class="form-control" type="number" min="1" required>
                    @error('isi_satuan_kecil') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Satuan Sedang</label>
                    <select name="satuan_sedang" class="form-control" required>
                        <option value="">-- Pilih Satuan --</option>
                        @foreach($satuanList as $satuan)
                        <option value="{{ $satuan }}"
                            {{ old('satuan_sedang', $product->satuan_sedang ?? '') == $satuan ? 'selected' : '' }}>
                            {{ $satuan }}
                        </option>
                        @endforeach
                    </select>
                    @error('satuan_sedang') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Isi Satuan Sedang</label>
                    <input name="isi_satuan_sedang" value="{{ old('isi_satuan_sedang', $product->isi_satuan_sedang ?? 1) }}" class="form-control" type="number" min="1">
                </div>
                <div class="form-group">
                    <label>Satuan Besar</label>
                    <select name="satuan_besar" class="form-control" required>
                        <option value="">-- Pilih Satuan --</option>
                        @foreach($satuanList as $satuan)
                        <option value="{{ $satuan }}"
                            {{ old('satuan_besar', $product->satuan_besar ?? '') == $satuan ? 'selected' : '' }}>
                            {{ $satuan }}
                        </option>
                        @endforeach
                    </select>
                    @error('satuan_besar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Isi Satuan Besar</label>
                    <input name="isi_satuan_besar" value="{{ old('isi_satuan_besar', $product->isi_satuan_besar ?? 1) }}" class="form-control" type="number" min="1">
                    @error('isi_satuan_besar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Satuan Massa</label>
                    <select name="satuan_massa" class="form-control">
                        <option value="">-- Pilih Satuan Massa --</option>
                        @foreach($satuanMassaList as $satuanMassa)
                        <option value="{{ $satuanMassa }}"
                            {{ old('satuan_massa', $product->satuan_massa ?? '') == $satuanMassa ? 'selected' : '' }}>
                            {{ $satuanMassa }}
                        </option>
                        @endforeach
                    </select>
                    @error('satuan_massa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Isi Satuan Massa</label>
                    <input name="isi_satuan_massa" value="{{ old('isi_satuan_massa', $product->isi_satuan_massa ?? 1) }}" class="form-control" type="number" min="1">
                    @error('isi_satuan_massa') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>Catatan</label>
                    <textarea name="catatan" class="form-control">{{ old('catatan', $product->catatan ?? '') }}</textarea>
                    @error('catatan') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
            </div>
        </div>
    </div>
    {{-- Harga Pokok --}}
    <div class="tab-pane fade" id="pokok" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <div class="form-group">
                    <label>HPP Bruto (Kecil)</label>
                    <input name="hpp_bruto_kecil" value="{{ old('hpp_bruto_kecil', $product->hpp_bruto_kecil ?? '') }}" class="form-control" type="number" step="0.01">
                    @error('hpp_bruto_kecil') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                <div class="form-group">
                    <label>HPP Bruto (Besar)</label>
                    <input name="hpp_bruto_besar" value="{{ old('hpp_bruto_besar', $product->hpp_bruto_besar ?? '') }}" class="form-control" type="number" step="0.01">
                    @error('hpp_bruto_besar') <div class="invalid-feedback">{{ $message }}</div> @enderror
                </div>
                @for($i=1;$i<=5;$i++)
                    <div class="form-group">
                    <label>Diskon HPP {{ $i }}</label>
                    <input name="diskon_hpp_{{ $i }}" value="{{ old('diskon_hpp_'.$i, $product->{'diskon_hpp_'.$i} ?? '') }}" class="form-control" type="number" step="0.01">
                    @error('diskon_hpp_'.$i) <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            @endfor
        </div>
    </div>
</div>
{{-- Harga Jual --}}
<div class="tab-pane fade" id="jual" role="tabpanel">
    <div class="row">
        <div class="col-md-6">
            <div class="form-group">
                <label>Harga Jual Umum</label>
                <input name="harga_umum" value="{{ old('harga_umum', $product->harga_umum ?? '') }}" class="form-control" type="number" step="0.01">
            </div>
            @for($i=1;$i<=5;$i++)
                <div class="form-group">
                <label>Diskon Harga {{ $i }}</label>
                <input name="diskon_harga_{{ $i }}" value="{{ old('diskon_harga_'.$i, $product->{'diskon_harga_'.$i} ?? '') }}" class="form-control" type="number" step="0.01">
        </div>
        @endfor
    </div>
</div>
</div>
</div>