<?php

namespace App\Http\Controllers\Stocks;

use App\Exports\StockCardExport;
use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Product;
use App\Models\PurchasesInvoice;
use App\Models\PurchasesReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $branches = CompanyBranch::orderBy('name')->get();
        return view('stocks.cards.index', compact('branches'));
    }

    public function datatable(Request $request)
    {
        $productId = $request->product_id;
        $branchId  = $request->lokasi_id;
        $awal      = $request->periode_awal  . ' 00:00:00';
        $akhir     = $request->periode_akhir . ' 23:59:59';

        $q = Stock::with(['product'])
            ->where('product_id', $productId)
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($branchId) {
            $q->where('lokasi_id', $branchId);
        }

        $rows = $q->get();

        // Hitung running saldo
        $saldo_awal = Stock::where('product_id', $productId)
            ->when($branchId, fn($x) => $x->where('lokasi_id', $branchId))
            ->where('created_at', '<', $awal)
            ->sum(DB::raw("IF(type='in', jumlah, IF(type='out' OR type='destroy', -jumlah, 0))"));

        $total_masuk = 0;
        $total_keluar = 0;
        $saldo = $saldo_awal;

        $result = [];
        foreach ($rows as $r) {
            $masuk = $r->type == 'in' ? $r->jumlah : 0;
            $keluar = ($r->type == 'out' || $r->type == 'destroy') ? $r->jumlah : 0;

            $saldo += $masuk - $keluar;
            $total_masuk += $masuk;
            $total_keluar += $keluar;

            $code = extractDocumentCodes($r->catatan, ['RT', 'HR', 'PI']);

            // get nama customer/supplier dari code dokumen
            $nama_customer_supplier = 'Tidak ada Nama';

            if (!empty($code)) {
                $docCode = $code[0];
                if (str_starts_with($docCode, 'RT')) {
                    if ($r->type === 'out') {
                        $nama_customer_supplier = optional(
                            PurchasesReturn::where('kode', $docCode)->first()
                        )->supplier()->value('name') ?? 'Tidak ada Nama';
                    } else {
                        $nama_customer_supplier = optional(
                            SalesReturn::where('kode', $docCode)->first()
                        )->customer()->value('name') ?? 'Tidak ada Nama';
                    }
                } elseif (str_starts_with($docCode, 'HR')) {
                    $nama_customer_supplier = optional(
                        SalesInvoice::where('kode', $docCode)->first()
                    )->customer()->value('name') ?? 'Tidak ada Nama';
                } elseif (str_starts_with($docCode, 'PI')) {
                    $nama_customer_supplier = optional(
                        PurchasesInvoice::where('kode', $docCode)->first()
                    )->supplier()->value('name') ?? 'Tidak ada Nama';
                }
            }

            $result[] = [
                'tanggal' => $r->created_at->format('Y-m-d H:i:s'),
                'tipe_stock' => ($r->type == 'in' ? '<span class="text-success">Stock Masuk</span>' : '<span class="text-danger">Stock Keluar</span>'), // set di Stock
                'catatan'   => $r->catatan ?? 'Tidak ada Catatan',   // set di Stock
                'nama'           => $nama_customer_supplier ?: 'Tidak ada Nama',
                'masuk' => $masuk ? $masuk . ' ' . $r->product->satuan_kecil : '',
                'keluar' => $keluar ? $keluar . ' ' . $r->product->satuan_kecil : '',
                'sisa'   => $saldo . ' ' . $r->product->satuan_kecil,
            ];
        }

        return response()->json([
            'data' => $result,
            'total_masuk' => $total_masuk,
            'total_keluar' => $total_keluar,
            'saldo_akhir' => $saldo,
            'satuan' => $rows->first()->product->satuan_kecil ?? '',
        ]);
    }

    public function export(Request $request)
    {
        $productId = $request->input('product_id');
        $branchId  = $request->input('lokasi_id');
        $awalDate  = $request->input('periode_awal');   // 'YYYY-MM-DD'
        $akhirDate = $request->input('periode_akhir');  // 'YYYY-MM-DD'
        $awal      = $awalDate ? ($awalDate.' 00:00:00') : null;
        $akhir     = $akhirDate ? ($akhirDate.' 23:59:59') : null;

        // Basic validation
        if (!$productId) {
            return redirect()->back()->withErrors(['product_id' => 'Produk harus dipilih']);
        }

        // Query rows in-range (same as datatable, but no HTML)
        $q = Stock::with(['product'])
            ->where('product_id', $productId)
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($branchId) {
            $q->where('lokasi_id', $branchId);
        }

        $rows = $q->get();

        // Saldo awal (before periode_awal)
        $saldo_awal = Stock::where('product_id', $productId)
            ->when($branchId, fn($x) => $x->where('lokasi_id', $branchId))
            ->where('created_at', '<', $awal)
            ->sum(DB::raw("IF(type='in', jumlah, IF(type IN ('out','destroy'), -jumlah, 0))"));

        $total_masuk  = 0;
        $total_keluar = 0;
        $saldo        = $saldo_awal;
        $result       = [];

        // Helper: extract doc codes exactly like your datatable
        $extract = fn($text) => extractDocumentCodes($text, ['RT','HR','PI']) ?? [];

        foreach ($rows as $r) {
            $masuk  = $r->type === 'in' ? (float)$r->jumlah : 0.0;
            $keluar = ($r->type === 'out' || $r->type === 'destroy') ? (float)$r->jumlah : 0.0;

            $saldo        += $masuk - $keluar;
            $total_masuk  += $masuk;
            $total_keluar += $keluar;

            // Who (customer/supplier) — same logic as your controller
            $nama = 'Tidak ada Nama';
            $codes = $extract($r->catatan ?? '');
            if (!empty($codes)) {
                $docCode = $codes[0];
                if (str_starts_with($docCode, 'RT')) {
                    if ($r->type === 'out') {
                        $nama = optional(PurchasesReturn::where('kode', $docCode)->first())
                                    ?->supplier()->value('name') ?? 'Tidak ada Nama';
                    } else {
                        $nama = optional(SalesReturn::where('kode', $docCode)->first())
                                    ?->customer()->value('name') ?? 'Tidak ada Nama';
                    }
                } elseif (str_starts_with($docCode, 'HR')) {
                    $nama = optional(SalesInvoice::where('kode', $docCode)->first())
                                ?->customer()->value('name') ?? 'Tidak ada Nama';
                } elseif (str_starts_with($docCode, 'PI')) {
                    $nama = optional(PurchasesInvoice::where('kode', $docCode)->first())
                                ?->supplier()->value('name') ?? 'Tidak ada Nama';
                }
            }

            $satuan = $rows->first()?->product?->satuan_kecil ?? '';

            $result[] = [
                'tanggal'    => $r->created_at->format('Y-m-d H:i:s'),
                'tipe_stock' => $r->type === 'in' ? 'Stock Masuk' : 'Stock Keluar',
                'catatan'    => $r->catatan ?? 'Tidak ada Catatan',
                'nama'       => $nama,
                'masuk'      => $masuk ? number_format($masuk, 0, ',', '.') . ' ' . $satuan : '',
                'keluar'     => $keluar ? number_format($keluar, 0, ',', '.') . ' ' . $satuan : '',
                'sisa'       => number_format($saldo, 0, ',', '.') . ' ' . $satuan,
            ];
        }

        $product = Product::findOrFail($productId);
        $produkName = "{$product->kode} - {$product->nama}";
        $satuan = $rows->first()?->product?->satuan_kecil ?? ($product->satuan_kecil ?? '');

        return Excel::download(
            new StockCardExport(
                rows: $result,
                produkName: $produkName,
                awal: $awalDate,
                akhir: $akhirDate,
                satuan: strtoupper($satuan ?? ''),
                totalMasuk: $total_masuk,
                totalKeluar: $total_keluar,
                saldoAkhir: $saldo
            ),
            'kartu_stok_'.$product->kode.'.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $productId  = $request->input('product_id');
        $branchId   = $request->input('lokasi_id');
        $awalDate   = $request->input('periode_awal');   // YYYY-MM-DD
        $akhirDate  = $request->input('periode_akhir');  // YYYY-MM-DD
        $awal       = $awalDate  ? ($awalDate.' 00:00:00')   : null;
        $akhir      = $akhirDate ? ($akhirDate.' 23:59:59')  : null;

        abort_unless($productId && $awal && $akhir, 400, 'product_id & periode are required');

        // Rows in range
        $q = Stock::with(['product'])
            ->where('product_id', $productId)
            ->whereBetween('created_at', [$awal, $akhir])
            ->orderBy('created_at')
            ->orderBy('id');

        if ($branchId) $q->where('lokasi_id', $branchId);

        $rows = $q->get();

        // Saldo awal
        $saldo_awal = Stock::where('product_id', $productId)
            ->when($branchId, fn($x) => $x->where('lokasi_id', $branchId))
            ->where('created_at', '<', $awal)
            ->sum(DB::raw("IF(type='in', jumlah, IF(type IN ('out','destroy'), -jumlah, 0))"));

        $total_masuk = 0;
        $total_keluar = 0;
        $saldo = $saldo_awal;
        $dataRows = [];

        $extract = fn($text) => extractDocumentCodes($text, ['RT','HR','PI']) ?? [];

        foreach ($rows as $r) {
            $masuk  = $r->type === 'in' ? (float)$r->jumlah : 0.0;
            $keluar = ($r->type === 'out' || $r->type === 'destroy') ? (float)$r->jumlah : 0.0;

            $saldo        += $masuk - $keluar;
            $total_masuk  += $masuk;
            $total_keluar += $keluar;

            // Nama customer/supplier (same logic as your datatable)
            $nama = 'Tidak ada Nama';
            $codes = $extract($r->catatan ?? '');
            if (!empty($codes)) {
                $docCode = $codes[0];
                if (str_starts_with($docCode, 'RT')) {
                    if ($r->type === 'out') {
                        $nama = optional(PurchasesReturn::where('kode', $docCode)->first())
                                ?->supplier()->value('name') ?? 'Tidak ada Nama';
                    } else {
                        $nama = optional(SalesReturn::where('kode', $docCode)->first())
                                ?->customer()->value('name') ?? 'Tidak ada Nama';
                    }
                } elseif (str_starts_with($docCode, 'HR')) {
                    $nama = optional(SalesInvoice::where('kode', $docCode)->first())
                            ?->customer()->value('name') ?? 'Tidak ada Nama';
                } elseif (str_starts_with($docCode, 'PI')) {
                    $nama = optional(PurchasesInvoice::where('kode', $docCode)->first())
                            ?->supplier()->value('name') ?? 'Tidak ada Nama';
                }
            }

            $satuan = $rows->first()?->product?->satuan_kecil ?? '';

            $dataRows[] = [
                'tanggal' => $r->created_at->format('Y-m-d H:i:s'),
                'tipe'    => $r->type === 'in' ? 'Stock Masuk' : 'Stock Keluar',
                'catatan' => $r->catatan ?? 'Tidak ada Catatan',
                'nama'    => $nama,
                'masuk'   => $masuk  ? number_format($masuk, 0, ',', '.')  . ' ' . strtoupper($satuan) : '',
                'keluar'  => $keluar ? number_format($keluar,0, ',', '.')  . ' ' . strtoupper($satuan) : '',
                'sisa'    => number_format($saldo, 0, ',', '.') . ' ' . strtoupper($satuan),
            ];
        }

        $product    = Product::findOrFail($productId);
        $produkName = "{$product->kode} - {$product->nama}";
        $satuan     = strtoupper($rows->first()?->product?->satuan_kecil ?? ($product->satuan_kecil ?? ''));

        $pdf = Pdf::loadView('stocks.cards.export_pdf', [
            'produkName'  => $produkName,
            'periodeText' => ($awalDate && $akhirDate)
                                ? date('d M Y', strtotime($awalDate)).' s/d '.date('d M Y', strtotime($akhirDate))
                                : '-',
            'satuan'      => $satuan,
            'totalMasuk'  => $total_masuk,
            'totalKeluar' => $total_keluar,
            'saldoAkhir'  => $saldo,
            'rows'        => $dataRows,
        ])->setPaper('a4','portrait'); // 'landscape' if you prefer

        return $pdf->download('kartu_stok_'.$product->kode.'.pdf');
    }
}
