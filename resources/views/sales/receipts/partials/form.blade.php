<form method="POST" action="{{ $receipt ? route('sales.receipts.update', $receipt->id) : route('sales.receipts.store') }}" class="form-horizontal">
    @if($receipt)
    @method('PUT')
    @endif
    @csrf
    <div class="row mb-2">
        <div class="col-md-3 mb-3">

            <label>No Faktur</label>
            <div class="input-group">
                <input
                    name="kode"
                    id="input-kode"
                    class="form-control @error('kode') is-invalid @enderror"
                    value="{{ old('kode', $receipt->kode ?? '') ?: '(auto)' }}"
                    @if(old('auto_kode', is_null(old('kode', $receipt->kode ?? null)) || old('kode', $receipt->kode ?? '') === '' ? 1 : 0)) readonly @endif
                autocomplete="off"
                @if($receipt && $receipt->kode) disabled @endif

                >
                <div class="input-group-text bg-light">
                    <input
                        type="checkbox"
                        id="auto_kode"
                        name="auto_kode"
                        value="1"
                        class="form-check-input mt-0"
                        {{ old('auto_kode', (is_null(old('kode', $receipt->kode ?? null)) || old('kode', $receipt->kode ?? '') === '') ? 1 : 0) ? 'checked' : '' }}
                        @if($receipt && $receipt->kode) disabled @endif
                    >
                    <label for="auto_kode" class="mb-0 ms-1" style="font-size: 0.93em; cursor:pointer;">Auto</label>
                </div>
            </div>
            @error('kode') <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Tanggal</label>
            <input type="date" name="tanggal" class="form-control @error('tanggal') is-invalid @enderror"
                value="{{ old('tanggal', $receipt->tanggal ?? date('Y-m-d')) }}" required>
            @error('tanggal') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Customer</label>
            <select name="company_profile_id" id="select-customer" class="form-control @error('company_profile_id') is-invalid @enderror" required>
                <option value="">-- Pilih Customer --</option>
                @foreach($customers as $c)
                <option value="{{ $c->id }}" {{ old('company_profile_id', $receipt->company_profile_id ?? '') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                @endforeach
            </select>
            @error('company_profile_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        <div class="col-md-3">
            <label>Kolektor</label>
            <select name="employee_id" class="form-control @error('employee_id') is-invalid @enderror" required>
                <option value="">-- Pilih Kolektor --</option>
                @foreach($employees as $emp)
                <option value="{{ $emp->id }}" {{ old('employee_id', $receipt->employee_id ?? '') == $emp->id ? 'selected' : '' }}>{{ $emp->nama }}</option>
                @endforeach
            </select>
            @error('employee_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
    </div>
    <div class="row mb-2">
        <div class="col-md-12">
            <label>Catatan</label>
            <input name="catatan" class="form-control" value="{{ old('catatan', $receipt->catatan ?? '') }}">
        </div>
    </div>

    @include('sales.receipts.partials.items-modal')

    <div class="row mt-3 mb-2">
        <div class="col-md-4 offset-md-8">
            <table class="table table-bordered">
                <tr>
                    <th class="text-end">Total Diterima</th>
                    <td class="text-end total-diterima"></td>
                </tr>
            </table>
        </div>
    </div>


    <div class="mt-4">
        <button class="btn btn-primary"><i class="fa fa-save"></i> Simpan</button>
        <a href="{{ route('sales.receipts.index') }}" class="btn btn-secondary">Batal</a>
    </div>
</form>

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
</script>
@endpush