<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Models\CompanyBranch;
use App\Models\Product;
use App\Models\PurchasesInvoice;
use App\Models\PurchasesReturn;
use App\Models\SalesInvoice;
use App\Models\SalesReturn;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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

            $code = extractDocumentCodes($r->catatan, ['RT', 'SI', 'PI']);

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
                } elseif (str_starts_with($docCode, 'SI')) {
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
}
