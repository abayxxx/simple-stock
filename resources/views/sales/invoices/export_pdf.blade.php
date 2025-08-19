<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Faktur Penjualan</title>
  <style>
    @page { margin: 16mm 12mm; } /* slightly tighter margins */
    body  { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color:#111; }
    h1    { font-size: 14px; margin: 0 0 4px 0; text-align: center; }
    .periode { text-align:center; margin-bottom: 8px; }

    table { width:100%; border-collapse: collapse; table-layout: fixed; }
    th, td { border:1px solid #999; padding:4px 4px; vertical-align:top; }
    th { background:#f2f2f2; text-align:left; }
    tfoot td { font-weight:bold; }

    /* let content wrap so it doesn't push width */
    th, td { word-break: break-word; }
    .date { white-space:nowrap; }   /* keep dates compact */
    .num  { text-align:right; }     /* remove nowrap to allow wrapping if needed */

    /* repeat header each page (dompdf-friendly) */
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr    { page-break-inside: avoid; }
  </style>
</head>
<body>
  <h1>DAFTAR FAKTUR PENJUALAN</h1>
  <div class="periode">Periode : {{ $periodeText }}</div>

  <table>
    <thead>
      <tr>
        <!-- widths sum to 100% -->
        <th style="width:10%">Tanggal</th>
        <th style="width:16%">No. Faktur</th>
        <th style="width:18%">Nama Customer</th>
        <th style="width:10%">Sales</th>
        <th style="width:10%">Jatuh Tempo</th>
        <th style="width:8%">Grand Total</th>
        <th style="width:8%">Total Retur</th>
        <th style="width:8%">Total Bayar</th>
        <th style="width:12%">Sisa Tagihan</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td class="date">{{ $r->tanggal ? \Carbon\Carbon::parse($r->tanggal)->format('d M Y') : '-' }}</td>
          <td>{{ $r->kode }}</td>
          <td>{{ $r->customer_name }}</td>
          <td>{{ $r->sales_name }}</td>
          <td class="date">{{ $r->jatuh_tempo ? \Carbon\Carbon::parse($r->jatuh_tempo)->format('d M Y') : '-' }}</td>
          <td class="num">{{ number_format((float)$r->grand_total, 2, ',', '.') }}</td>
          <td class="num">{{ number_format((float)$r->total_retur, 2, ',', '.') }}</td>
          <td class="num">{{ number_format((float)$r->total_bayar, 2, ',', '.') }}</td>
          <td class="num">{{ number_format((float)$r->sisa_tagihan, 2, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="9" style="text-align:center; padding:10px">Tidak ada data</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="8" style="text-align:right">TOTAL PENJUALAN</td>
        <td class="num">{{ number_format((float)$total, 2, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
