<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Tanda Terima Penjualan</title>
  <style>
    @page { margin: 16mm 14mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#111; }
    h1 { font-size: 14px; margin: 0 0 6px 0; text-align:center; }
    .periode { text-align:center; margin-bottom: 10px; }
    table { width:100%; border-collapse:collapse; }
    th, td { border:1px solid #888; padding:5px 6px; }
    th { background:#f3f3f3; text-align:left; }
    tfoot td { font-weight:bold; }
    .num { text-align:right; white-space:nowrap; }
    .date { white-space:nowrap; }
  </style>
</head>
<body>
  <h1>DAFTAR TANDA TERIMA PENJUALAN</h1>
  <div class="periode">Periode: {{ $periodeText }}</div>

  <table>
    <thead>
      <tr>
        <th style="width:14%">Tanggal</th>
        <th style="width:20%">No. TT</th>
        <th>Nama Customer</th>
        <th style="width:14%">Kolektor</th>
        <th style="width:10%">Jml Faktur</th>
        <th style="width:16%" class="num">Grand Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td class="date">{{ $r->tanggal ? \Carbon\Carbon::parse($r->tanggal)->format('d M Y') : '-' }}</td>
          <td>{{ $r->kode }}</td>
          <td>{{ $r->customer_name }}</td>
          <td>{{ $r->collector_name }}</td>
          <td class="num">{{ (int)$r->jml_faktur }}</td>
          <td class="num">{{ number_format((float)$r->grand_total, 2, ',', '.') }}</td>
        </tr>
      @empty
        <tr><td colspan="6" style="text-align:center; padding:12px">Tidak ada data</td></tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" class="num">TOTAL PENJUALAN</td>
        <td class="num">{{ number_format((float)$total, 2, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
