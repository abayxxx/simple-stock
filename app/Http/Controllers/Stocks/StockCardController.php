<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        return view('stocks.cards.index', compact('products', 'branches'));
    }

    public function datatable(Request $request)
    {
        $productId = $request->product_id;
        $branchId  = $request->lokasi_id;
        $awal      = $request->periode_awal ?? now()->format('Y-m-d');
        $akhir     = $request->periode_akhir ?? now()->format('Y-m-d');

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

            $result[] = [
                'tanggal' => $r->created_at->format('Y-m-d H:i:s'),
                'transaksi_dari' => $r->transaksi_dari ?? ($r->type == 'in' ? '<span class="text-success">Stock Masuk</span>' : '<span class="text-danger">Stock Keluar</span>'), // set di Stock
                'no_transaksi'   => $r->no_transaksi ?? 'Tidak ada No Transaksi',   // set di Stock
                'nama'           => $r->nama_customer_supplier ?? 'Tidak ada Nama', // set di Stock
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
}
