<?php

namespace App\Http\Controllers\Sales;

use App\Exports\SalesUnpaidExport;
use App\Http\Controllers\Controller;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Maatwebsite\Excel\Facades\Excel;

class SalesUnpaidController extends Controller
{
    public function index()
    {
        return view('sales.unpaid.index');
    }

    public function data(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'salesGroup'])
            ->where('sisa_tagihan', '>', 0);

        if ($request->from) $query->where('tanggal', '>=', $request->from);
        if ($request->to) $query->where('tanggal', '<=', $request->to);

        // Custom search: customer, etc
        if ($request->customer) {
            $query->whereHas('customer', function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->customer . '%');
            });
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($row) => tanggal_indo($row->tanggal))
            ->addColumn('nama_group', fn($row) => $row->salesGroup->nama ?? '-')
            ->editColumn('grand_total', fn($row) => number_format($row->grand_total,2,',','.'))
            ->editColumn('total_bayar', fn($row) => number_format($row->total_bayar,2,',','.'))
            ->editColumn('sisa_tagihan', fn($row) => number_format($row->sisa_tagihan,2,',','.'))
            ->addColumn('customer', fn($row) => $row->customer->name ?? '')
            ->addColumn('alamat', fn($row) => $row->customer->alamat ?? '')
            ->make(true);
    }

    public function export(Request $request)
    {
        // (Optional: if pakai Laravel Excel)
        return Excel::download(new SalesUnpaidExport($request), 'Faktur-Belum-Lunas.xlsx');
    }
}

