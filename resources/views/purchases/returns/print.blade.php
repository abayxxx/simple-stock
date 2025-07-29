<!DOCTYPE html>
<html>

<head>
    <title>Faktur Return Pembelian - {{ $invoice->kode }}</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            font-size: 14px;
        }

        .border {
            border: 1px solid #c1a13d;
        }

        table {
            border-collapse: collapse;
            width: 100%;
        }

        th,
        td {
            border: 1px solid #c1a13d;
            padding: 5px;
        }

        th {
            background: #fffbe8;
        }

        .noborder {
            border: none;
        }

        .text-end {
            text-align: right;
        }

        .fw-bold {
            font-weight: bold;
        }

        .title {
            font-size: 16px;
            font-weight: bold;
        }

        .small {
            font-size: 12px;
        }
    </style>
</head>

<body>
    <div class="border" style="padding:18px; margin: 10px;">
        <div style="display: flex; justify-content: space-between;">
            <div>
                <b>Kepada Yth,</b><br>
                {{ $invoice->customer->name ?? '-' }}<br>
                {{ $invoice->customer->address ?? '' }}<br>
                {{ $invoice->customer->city ?? '' }}
            </div>
            <div>
                <table class="noborder" style="width:auto;">
                    <tr>
                        <td class="noborder"><b>No Faktur :</b></td>
                        <td class="noborder">{{ $invoice->kode }}</td>
                    </tr>
                    <tr>
                        <td class="noborder"><b>Tanggal :</b></td>
                        <td class="noborder">{{ tanggal_indo($invoice->tanggal) }}</td>
                    </tr>
                    <tr>
                        <td class="noborder"><b>Pot. No Faktur :</b></td>
                        <td class="noborder">{{ $invoice->purchasesInvoice->kode ?? '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
        <br>
        <table>
            <thead>
                <tr>
                    <th>No.</th>
                    <th>Nama Barang</th>
                    <th>No. Batch</th>
                    <th>Tgl. Expired</th>
                    <th>Qty.</th>
                    <th>Harga @</th>
                    <th>Disc. 1</th>
                    <th>Disc. 2</th>
                    <th>PPN (%)</th>
                    <th>Jumlah</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items as $idx => $item)
                <tr>
                    <td class="text-end">{{ $idx + 1 }}</td>
                    <td>{{ $item->product->nama ?? '-' }}</td>
                    <td>{{ $item->no_seri ?? '-' }}</td>
                    <td>{{ $item->tanggal_expired ?? '-' }}</td>
                    <td>{{ $item->qty }} {{ $item->product->satuan_kecil ?? '' }}</td>
                    <td class="text-end">{{ number_format($item->harga_satuan,0,',','.') }}</td>
                    <td class="text-end">{{ $item->diskon_1_persen ?? 0 }}</td>
                    <td class="text-end">{{ $item->diskon_2_persen ?? 0 }}</td>
                    <td class="text-end">{{ number_format($item->ppn_persen ?? 0,0,',','.') }}</td>
                    <td class="text-end">{{ number_format($item->sub_total_setelah_disc ?? 0,0,',','.') }}</td>
                </tr>
                @endforeach
            </tbody>
        </table>
        <table style="width:100%; border-collapse:collapse; margin-top:32px; font-size:14px;">
            <tr>
                <td style="width:22%;  height:65px; vertical-align:top; padding:6px;">
                    <b>Diterima Oleh,</b>
                    <br><br>
                </td>
                <td style="width:26%;  height:65px; padding:0;  border-left:none; margin:0; vertical-align:top;">
                    <table style="width:100%; border:none; border-collapse:collapse; padding:0;">
                        <tr>
                            <td colspan="2"><b>Diperiksa Oleh,</b></td>
                        </tr>
                        <tr>
                            <td style="width:50%; height:100px;  vertical-align:bottom; text-align:center; font-weight:bold; ">ADMIN</td>
                            <td style="width:50%; height:100px; text-align:center; vertical-align:bottom; font-weight:bold; ">GUDANG</td>
                        </tr>
                    </table>
                </td>
                <td style="width:52%; border-left:none; vertical-align:top; padding:0;">
                    <table style="width:100%; border-collapse:collapse;">
                        <tr>
                            <td style="border-bottom:1px solid; text-align:right; font-size:18px; font-weight:bold;" colspan="2">
                                GRAND TOTAL
                                <span style="font-size:20px; margin-left:28px;">{{ number_format($invoice->grand_total,0,',','.') }}</span>
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:5px 0 25px 10px;">
                                <b style="font-family:monospace;">Terbilang</b> : {{ ucfirst(terbilang($invoice->grand_total)) }} Rupiah.
                            </td>
                        </tr>
                        <tr>
                            <td colspan="2" style="padding:2px 0 5px 10px;">
                                <b>NB :</b> Barang <b>RETUR</b> harap dilampirkan Faktur
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        <div style="display:flex;justify-content:space-between;margin-top:10px;">
            <span style="font-size:13px;">Waktu Dicetak : {{ date('H:i') }}</span>
            <span style="font-size:13px;">1 of 1</span>
        </div>

    </div>
    <script>
        window.print();
    </script>
</body>

</html>