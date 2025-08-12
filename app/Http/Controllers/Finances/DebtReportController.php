<?php
// app/Http/Controllers/Finances/DebtReportController.php

namespace App\Http\Controllers\Finances;

use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;
use App\Models\PurchasesInvoice;

class DebtReportController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('tanggal', now()->toDateString());

        if ($request->ajax()) {
            $supplierSummary = PurchasesInvoice::select([
                    'company_profile_id',
                    DB::raw('SUM(grand_total) as total_debet'),
                    DB::raw('SUM(total_bayar) as total_kredit'),
                    DB::raw('SUM(sisa_tagihan) as total_sisa')
                ])
                ->with('supplier')
                // ->where('sisa_tagihan', '>', 0)
                ->whereDate('tanggal', '<=', $date)
                ->groupBy('company_profile_id')
                ->get();

            // Transform for DataTables (with supplier name, kategori)
            $data = $supplierSummary->map(function ($row) {
                return [
                    'kode'         => $row->supplier->code ?? '-',
                    'nama'         => $row->supplier->name ?? '-',
                    'kategori'     => $row->supplier->kategori ?? '-',
                    'debet'        => number_format($row->total_debet, 2, ',', '.'),
                    'kredit'       => number_format($row->total_kredit, 2, ',', '.'),
                    'sisa'         => number_format($row->total_sisa, 2, ',', '.'),
                    'sisa_raw'     => $row->total_sisa, // for row coloring
                ];
            });

            return DataTables::of($data)
                ->rawColumns(['nama'])
                ->make(true);
        }

        return view('finances.debt.index', compact('date'));
    }
}
