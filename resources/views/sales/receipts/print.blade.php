<!DOCTYPE html>
<html>

<head>
    <title>Cetak Tanda Terima</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
        }

        .outer-border {
            border: 2px solid #C9A93E;
            padding: 30px 28px 36px 28px;
            margin: 18px auto;
            max-width: 950px;
            min-height: 1200px;
        }

        .tt-title {
            text-align: center;
            font-weight: bold;
            font-size: 19px;
            margin-bottom: 5px;
            margin-top: 8px;
            letter-spacing: 1.5px;
        }

        .tt-info {
            width: 100%;
            margin-bottom: 8px;
        }

        .tt-info td {
            font-size: 13px;
            vertical-align: top;
        }

        .tt-faktur {
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 4px;
        }

        .bordered {
            border: 1px solid #222;
            border-collapse: collapse;
        }

        .bordered th,
        .bordered td {
            border: 1px solid #222;
            padding: 4px 7px;
        }

        .bordered th {
            background: #f6f6f6;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .text-bold {
            font-weight: bold;
        }

        .big-total {
            font-size: 1.18em;
            font-weight: bold;
        }

        .terbilang-box {
            border: 1px solid #333;
            padding: 5px 7px;
            margin-top: 13px;
            margin-bottom: 8px;
            font-size: 13px;
        }

        .tt-signature-table {
            width: 100%;
            margin-top: 28px;
        }

        .signature-space {
            min-height: 48px;
            height: 48px;
            vertical-align: bottom;
        }

        .tt-footer-table td {
            font-size: 13px;
            vertical-align: bottom;
        }

        .signature-name {
            margin-top: 26px;
            display: inline-block;
            border-bottom: 1px solid #444;
            width: 160px;
            text-align: center;
        }

        @media print {
            body {
                margin: 0;
            }

            .outer-border {
                margin: 0;
                padding: 24px;
            }
        }
    </style>
</head>

<body>
    <div class="outer-border">
        <div class="tt-title">TANDA TERIMA</div>
        <table class="tt-info">
            <tr>
                <td style="width:65%">
                    Telah diterima dari : <b>{{ $receipt->customer->name ?? '-' }}</b><br>
                    {{ $receipt->customer->address ?? '' }}<br>
                    {{ $receipt->customer->kota ?? '' }}
                </td>
                <td style="width:35%; text-align:right;">
                    No. Tanda Terima : <b>{{ $receipt->kode }}</b>
                </td>
            </tr>
        </table>
        <div class="tt-faktur">
            Faktur sebanyak {{ count($receipt->receiptItems) }} lembar, sebagai berikut:
        </div>
        <table class="bordered" width="100%">
            <thead>
                <tr>
                    <th class="text-center" style="width:28px;">No.</th>
                    <th class="text-center" style="width:110px;">No. Faktur</th>
                    <th class="text-center" style="width:80px;">Tanggal</th>
                    <th class="text-center" style="width:110px;">Total Faktur</th>
                    <th class="text-center">Keterangan</th>
                </tr>
            </thead>
            <tbody>
                @php
                $totalFaktur = 0;
                $totalRetur = 0;
                @endphp
                @foreach($receipt->receiptItems as $i => $item)
                <tr>
                    <td class="text-center">{{ $i+1 }}</td>
                    <td class="text-center">{{ $item->invoice->kode ?? '-' }}</td>
                    <td class="text-center">
                        {{ $item->invoice->tanggal ? \Carbon\Carbon::parse($item->invoice->tanggal)->format('d M Y') : '-' }}
                    </td>
                    <td class="text-right">Rp. {{ number_format($item->sisa_tagihan,0,',','.') }}</td>
                    <td>{{ $item->keterangan }}</td>
                </tr>
                @php
                $totalFaktur += $item->total_faktur;
                $totalRetur += $item->total_retur;
                @endphp
                @endforeach

                <tr>
                    <th colspan="3" class="text-right big-total">GRAND TOTAL</th>
                    <th colspan="1" class="text-right big-total">Rp. {{ number_format($receipt->receiptItems->sum('sisa_tagihan'), 2, ',', '.') }}</th>
                    <th></th>
                </tr>
            </tbody>
        </table>
        <div class="terbilang-box">
            <b>Terbilang:</b>
            {{ ucfirst(terbilang($receipt->receiptItems->sum('sisa_tagihan'))) }} Rupiah.
        </div>
        <table class="tt-footer-table" width="100%">
            <tr>
                <td style="width:64%;">
                    Kembali Tagih Tanggal :
                    {{ $receipt->kembali_tagih_tanggal ? \Carbon\Carbon::parse($receipt->kembali_tagih_tanggal)->format('d M Y') : '____________________' }}
                </td>
                <td class="text-right" style="width:36%;">
                    Medan, {{ \Carbon\Carbon::parse($receipt->tanggal)->format('d M Y') }}
                </td>
            </tr>
        </table>
        <table class="tt-signature-table" width="100%" style="margin-top:35px;">
            <tr>
                <td class="text-center" style="width:50%;">
                    <div class="signature-space"></div>
                    <span class="signature-name"></span><br>
                </td>

                <td class="text-center" style="width:50%;">
                    Diterima Oleh:<br>
                    <div class="signature-space"></div>
                    <span class="signature-name"></span>
                </td>
            </tr>
        </table>
    </div>
    <script>
        window.print();
    </script>
</body>

</html>