<?php

namespace App\Http\Controllers\Stocks;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Stock;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;


class StockListController extends Controller
{
    public function index(Request $request)
    {
        return view('stocks.lists.index');
    }

    public function datatable(Request $request)
    {
        // Tanggal periode
        $awal  = $request->input('periode_awal') ?? now()->format('Y-m-d');
        $akhir = $request->input('periode_akhir') ?? now()->format('Y-m-d');

        // Ambil semua produk (bisa filter aktif, dll)
        $products = Product::query();

        // if ($awal && $akhir) {
        //     $products->whereBetween('created_at', [$awal, $akhir]);
        // }

        return DataTables::of($products)
            ->addColumn('harga_umum', function ($r) {
                return number_format($r->harga_umum, 2, ',', '.');
            })
            ->addColumn('akhir', function ($r) use ($awal, $akhir) {
                // Ambil stok akhir hanya sampai tanggal filter
                $in = \App\Models\Stock::where('product_id', $r->id)
                    ->where('type', 'in')
                    ->whereDate('created_at', '<=', $akhir)
                    ->sum('jumlah');
                $out = \App\Models\Stock::where('product_id', $r->id)
                    ->where('type', 'out')
                    ->whereDate('created_at', '<=', $akhir)
                    ->sum('jumlah');
                $destroy = \App\Models\Stock::where('product_id', $r->id)
                    ->where('type', 'destroy')
                    ->whereDate('created_at', '<=', $akhir)
                    ->sum('jumlah');
                $sisa = $in - $out - $destroy;
                return $sisa . ' ' . strtoupper($r->satuan_kecil);
            })
            ->make(true);
    }
}
