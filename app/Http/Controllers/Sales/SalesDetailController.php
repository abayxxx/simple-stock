<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\SalesInvoiceItem;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\SalesDetailExport;
use Yajra\DataTables\Facades\DataTables;

class SalesDetailController extends Controller
{
    public function index()
    {
        return view('sales.detail.index');
    }

    public function datatable(Request $request)
    {
        $query = SalesInvoiceItem::with(['invoice.customer', 'product'])
            ->whereHas('invoice', function ($q) use ($request) {
                if ($request->from) $q->whereDate('tanggal', '>=', $request->from . ' 00:00:00');
                if ($request->to) $q->whereDate('tanggal', '<=', $request->to . ' 23:59:59');
            });

        return DataTables::of($query)
            ->addColumn('faktur_no', fn($r) => $r->invoice->kode)
            ->addColumn('tanggal', fn($r) => tanggal_indo($r->invoice->tanggal))
            ->addColumn('customer', fn($r) => $r->invoice->customer->name ?? '')
            ->addColumn('alamat', fn($r) => $r->invoice->customer->address ?? '')
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

        // Query item join invoice, customer, product, (bisa pakai with atau join)
        $items = SalesInvoiceItem::with(['invoice.customer', 'product'])
            ->whereHas('invoice', function ($q) use ($from, $to) {
                $q->whereBetween('tanggal', [$from . ' 00:00:00', $to . ' 23:59:59']);
            })
            ->orderBy('sales_invoice_id')
            ->orderBy('product_id')
            ->get();

        // Group by faktur_no
        $grouped = $items->groupBy(function ($item) {
            return $item->invoice->kode ?? '-';
        });

        return Excel::download(new SalesDetailExport($grouped), 'FakturPenjualanDetail.xlsx');
    }
}
