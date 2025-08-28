<table>
    <thead>
        <tr>
            <th>NO.</th>
            <th>TANGGAL</th>
            <th>NAMA</th>
            <th>ALAMAT</th>
            <th>NAMA PRODUK</th>
            <th>QTY.</th>
            <th>SATUAN</th>
            <th>HARGA (KECIL)</th>
            <th>DISC. 1 (NILAI)</th>
            <th>DISC. 2 (NILAI)</th>
            <th>SUB TOTAL</th>
            <th>Catatan</th>
        </tr>
    </thead>
    <tbody>
        @php
        $grandQty = 0; $grandDisc1 = 0; $grandDisc2 = 0; $grandSubtotal = 0;
        @endphp
        @foreach($grouped as $kode => $items)
            @php
                $subQty = 0; $subDisc1 = 0; $subDisc2 = 0; $subSubtotal = 0;
                $invoice = $items->first()->invoice ?? null;
                $customer = $invoice->customer ?? null;
            @endphp
            {{-- Group Header --}}
            <tr style="background: #B7F5BF; font-weight: bold;">
                <td colspan="11">NO. : {{ $kode }}</td>
            </tr>
            @foreach($items as $row)
                @php
                    $qty = $row->qty ?? 0;
                    $disc1 = $row->diskon_1_rupiah ?? 0;
                    $disc2 = $row->diskon_2_rupiah ?? 0;
                    $subtotal = $row->sub_total_setelah_disc ?? 0;
                    $subQty += $qty; $subDisc1 += $disc1; $subDisc2 += $disc2; $subSubtotal += $subtotal;
                    $grandQty += $qty; $grandDisc1 += $disc1; $grandDisc2 += $disc2; $grandSubtotal += $subtotal;
                @endphp
                <tr style="background: #FFFCC1;">
                    <td></td>
                    <td>{{ tanggal_indo($invoice->tanggal ?? '') }}</td>
                    <td>{{ $customer->name ?? '' }}</td>
                    <td>{{ $customer->alamat ?? '' }}</td>
                    <td>{{ $row->product->nama ?? '' }}</td>
                    <td style="text-align:right;">{{ number_format($qty, 2, ',', '.') }}</td>
                    <td>{{ $row->satuan ?? '' }}</td>
                    <td style="text-align:right;">{{ number_format($row->harga_satuan ?? 0, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($disc1, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($disc2, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ number_format($subtotal, 2, ',', '.') }}</td>
                    <td style="text-align:right;">{{ $row->catatan ?? '' }}</td>

                </tr>
            @endforeach
            {{-- Subtotal per faktur --}}
            <tr style="background: #efefef; font-weight:bold;">
                <td colspan="5" style="text-align:right">SUBTOTAL</td>
                <td style="text-align:right">{{ number_format($subQty, 2, ',', '.') }}</td>
                <td></td>
                <td></td>
                <td style="text-align:right">{{ number_format($subDisc1, 2, ',', '.') }}</td>
                <td style="text-align:right">{{ number_format($subDisc2, 2, ',', '.') }}</td>
                <td style="text-align:right">{{ number_format($subSubtotal, 2, ',', '.') }}</td>
            </tr>
        @endforeach
        {{-- Grand total footer --}}
        <tr style="background: #f8f8f8; font-weight: bold;">
            <td colspan="5" style="text-align:right">GRAND TOTAL</td>
            <td style="text-align:right">{{ number_format($grandQty, 2, ',', '.') }}</td>
            <td></td>
            <td></td>
            <td style="text-align:right">{{ number_format($grandDisc1, 2, ',', '.') }}</td>
            <td style="text-align:right">{{ number_format($grandDisc2, 2, ',', '.') }}</td>
            <td style="text-align:right">{{ number_format($grandSubtotal, 2, ',', '.') }}</td>
        </tr>
    </tbody>
</table>
