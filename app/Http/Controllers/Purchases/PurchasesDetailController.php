<?php

namespace App\Http\Controllers\Purchases;

use App\Exports\PurchasesDetailExport;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Models\PurchasesInvoiceItem;
use Yajra\DataTables\Facades\DataTables;

class PurchasesDetailController extends Controller
{
    public function index()
    {
        return view('purchases.detail.index');
    }

    public function datatable(Request $request)
    {
        $query = PurchasesInvoiceItem::with(['invoice.supplier', 'product'])
            ->whereHas('invoice', function ($q) use ($request) {
                if ($request->from) $q->whereDate('tanggal', '>=', $request->from . ' 00:00:00');
                if ($request->to) $q->whereDate('tanggal', '<=', $request->to . ' 23:59:59');
            });

        return DataTables::of($query)
            ->addColumn('faktur_no', fn($r) => $r->invoice->kode)
            ->addColumn('tanggal', fn($r) => tanggal_indo($r->invoice->tanggal))
            ->addColumn('supplier', fn($r) => $r->invoice->supplier->name ?? '')
            ->addColumn('alamat', fn($r) => $r->invoice->supplier->address ?? '')
            ->addColumn('product', fn($r) => $r->product->nama ?? '')
            ->addColumn('qty', fn($r) => $r->qty)
            ->addColumn('satuan', fn($r) => strtoupper($r->satuan ?? ''))
            ->addColumn('harga', fn($r) => number_format($r->harga_satuan, 2, ',', '.'))
            ->addColumn('disc_1', fn($r) => number_format($r->diskon_1_rupiah, 2, ',', '.'))
            ->addColumn('disc_2', fn($r) => number_format($r->diskon_2_rupiah, 2, ',', '.'))
            ->addColumn('sub_total', fn($r) => number_format($r->sub_total_setelah_disc, 2, ',', '.'))
            ->rawColumns(['faktur_no'])
            ->make(true);
    }

    public function exportExcel(Request $request)
    {
        $from = $request->input('from', date('Y-m-01'));
        $to   = $request->input('to', date('Y-m-d'));

        // Query item join invoice, supplier, product, (bisa pakai with atau join)
        $items = PurchasesInvoiceItem::with(['invoice.supplier', 'product'])
            ->whereHas('invoice', function ($q) use ($from, $to) {
                $q->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->orderBy('purchases_invoice_id')
            ->orderBy('product_id')
            ->get();

        // Group by faktur_no
        $grouped = $items->groupBy(function ($item) {
            return $item->invoice->kode ?? '-';
        });

        return Excel::download(new PurchasesDetailExport($grouped), 'FakturPembelianDetail.xlsx');
    }
}
