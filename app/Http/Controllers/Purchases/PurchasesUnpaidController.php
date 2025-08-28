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
            ->filterColumn('supplier', function ($query, $keyword) {
                $query->whereHas('supplier', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%$keyword%");
                });
            })
            ->filterColumn('alamat', function ($query, $keyword) {
                $query->whereHas('supplier', function ($q) use ($keyword) {
                    $q->where('alamat', 'like', "%$keyword%");
                });
            }) 
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->where('tanggal', 'like', "%$keyword%");
            }) 
            ->filterColumn('kode', function ($query, $keyword) {
                $query->where('kode', 'like', "%$keyword%");
            })
            ->filterColumn('grand_total', function ($query, $keyword) {
                $query->where('grand_total', 'like', "%".str_replace(['.'], '', $keyword)."%");
            })
            ->filterColumn('total_bayar', function ($query, $keyword) {
                $query->where('total_bayar', 'like', "%".str_replace(['.'], '', $keyword)."%");
            })
            ->filterColumn('sisa_tagihan', function ($query, $keyword) {
                $query->where('sisa_tagihan', 'like', "%".str_replace(['.'], '', $keyword)."%");
            })
            ->make(true);
    }

    public function export(Request $request)
    {
        // (Optional: if pakai Laravel Excel)
        return Excel::download(new PurchasesUnpaidExport($request), 'Faktur-Belum-Lunas.xlsx');
    }
}

