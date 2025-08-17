<?php

namespace App\Http\Controllers\Purchases;

use App\Exports\PurchasesUnpaidExport;
use App\Http\Controllers\Controller;
use App\Models\PurchasesInvoice;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class PurchasesUnpaidController extends Controller
{
    public function index()
    {
        return view('purchases.unpaid.index');
    }

    public function data(Request $request)
    {
        $query = PurchasesInvoice::with(['supplier'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->from) $query->where('tanggal', '>=', $request->from);
        if ($request->to) $query->where('tanggal', '<=', $request->to);

        // Custom search: supplier, etc
        if ($request->supplier) {
            $query->whereHas('supplier', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->supplier . '%');
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($row) => tanggal_indo($row->tanggal))
            ->editColumn('grand_total', fn($row) => number_format($row->grand_total,2,',','.'))
            ->editColumn('total_bayar', fn($row) => number_format($row->total_bayar,2,',','.'))
            ->editColumn('sisa_tagihan', fn($row) => number_format($row->sisa_tagihan,2,',','.'))
            ->addColumn('supplier', fn($row) => $row->supplier->name ?? '')
            ->addColumn('alamat', fn($row) => $row->supplier->alamat ?? '')
            ->make(true);
    }

    public function export(Request $request)
    {
        // (Optional: if pakai Laravel Excel)
        return Excel::download(new PurchasesUnpaidExport($request), 'Faktur-Belum-Lunas.xlsx');
    }
}

