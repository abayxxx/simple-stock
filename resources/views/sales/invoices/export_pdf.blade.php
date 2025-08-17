<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Daftar Faktur Penjualan</title>
  <style>
    @page { margin: 20mm 15mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 4px 0; text-align: center; }
    .periode { text-align: center; margin-bottom: 10px; }
    table { width: 100%; border-collapse: collapse; }
    th, td { border: 1px solid #999; padding: 6px 5px; }
    th { background: #f2f2f2; text-align: left; }
    tfoot td { font-weight: bold; }
    .num { text-align: right; white-space: nowrap; }
    .date { white-space: nowrap; }
  </style>
</head>
<body>
  <h1>DAFTAR FAKTUR PENJUALAN</h1>
  <div class="periode">Periode : {{ $periodeText }}</div>

  <table>
    <thead>
      <tr>
        <th style="width: 14%">Tanggal</th>
        <th style="width: 20%">No. Faktur</th>
        <th>Nama Customer</th>
        <th style="width: 12%">Sales</th>
        <th style="width: 14%">Jatuh Tempo</th>
        <th style="width: 16%" class="num">Grand Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse ($rows as $r)
        <tr>
          <td class="date">{{ $r->tanggal ? \Carbon\Carbon::parse($r->tanggal)->format('d M Y') : '-' }}</td>
          <td>{{ $r->kode }}</td>
          <td>{{ $r->customer_name }}</td>
          <td>{{ $r->sales_name }}</td>
          <td class="date">
            {{ $r->jatuh_tempo ? \Carbon\Carbon::parse($r->jatuh_tempo)->format('d M Y') : '-' }}
          </td>
          <td class="num">{{ number_format((float)$r->grand_total, 2, ',', '.') }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" style="text-align:center; padding:12px">Tidak ada data</td>
        </tr>
      @endforelse
    </tbody>
    <tfoot>
      <tr>
        <td colspan="5" style="text-align:right">TOTAL PENJUALAN</td>
        <td class="num">{{ number_format((float)$total, 2, ',', '.') }}</td>
      </tr>
    </tfoot>
  </table>
</body>
</html>
