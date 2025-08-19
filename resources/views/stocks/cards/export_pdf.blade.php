<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title>Kartu Stok</title>
  <style>
    @page { margin: 18mm 15mm; }
    body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color:#111; }
    h1 { font-size: 16px; margin: 0 0 6px 0; text-align:center; }

    .meta { margin: 0 0 8px 0; }
    .meta .line { margin: 2px 0; text-align:center; }

    table { width:100%; border-collapse: collapse; }
    th, td { border:1px solid #999; padding:6px 5px; vertical-align: top; }
    th { background:#f2f2f2; text-align:left; }
    tfoot td { font-weight:bold; }

    .num { text-align:right; white-space:nowrap; }
    .nowrap { white-space:nowrap; }

    /* repeat header on each page */
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    tr { page-break-inside: avoid; }
  </style>
</head>
<body>
  <h1>KARTU STOK</h1>
  <div class="meta">
    <div class="line">Produk : {{ $produkName }}</div>
    <div class="line">Periode : {{ $periodeText }}</div>
    <div class="line">
      TOTAL MASUK: {{ number_format($totalMasuk, 0, ',', '.') }} {{ $satuan }}
      &nbsp;&nbsp;&nbsp;
      TOTAL KELUAR: {{ number_format($totalKeluar, 0, ',', '.') }} {{ $satuan }}
      &nbsp;&nbsp;&nbsp;
      SISA: {{ number_format($saldoAkhir, 0, ',', '.') }} {{ $satuan }}
    </div>
  </div>

  <table>
    <thead>
      <tr>
        <th style="width:18%">Tanggal</th>
        <th style="width:14%">Transaksi Dari</th>
        <th>No Transaksi</th>
        <th style="width:22%">Nama</th>
        <th style="width:10%">Masuk</th>
        <th style="width:10%">Keluar</th>
        <th style="width:12%">Sisa</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td class="nowrap">{{ $r['tanggal'] }}</td>
          <td>{{ $r['tipe'] }}</td>
          <td>{{ $r['catatan'] }}</td>
          <td>{{ $r['nama'] }}</td>
          <td class="num">{{ $r['masuk'] }}</td>
          <td class="num">{{ $r['keluar'] }}</td>
          <td class="num">{{ $r['sisa'] }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="7" style="text-align:center; padding:12px">Tidak ada data</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
