<?php
// app/Http/Controllers/Finances/ReceiveableReportController.php

namespace App\Http\Controllers\Finances;

use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;


class ReceiveableReportController extends Controller
{
    public function index(Request $request)
    {
        $date = $request->get('tanggal', now()->toDateString() ?? now()->format('Y-m-d') . ' 23:59:59');

        if ($request->ajax()) {
            $customerSummary = SalesInvoice::select([
                    'company_profile_id',
                    DB::raw('SUM(grand_total) as total_debet'),
                    DB::raw('SUM(total_bayar + total_retur) as total_kredit'),
                    DB::raw('SUM(sisa_tagihan) as total_sisa')
                ])
                ->with('customer')
                // ->where('sisa_tagihan', '>', 0)
                ->whereDate('tanggal', '<=', $date)
                ->groupBy('company_profile_id')
                ->get();

            // Transform for DataTables (with customer name, kategori)
            $data = $customerSummary->map(function ($row) {
                return [
                    'kode'         => $row->customer->code ?? '-',
                    'nama'         => $row->customer->name ?? '-',
                    'kategori'     => $row->customer->kategori ?? '-',
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

        return view('finances.receivables.index', compact('date'));
    }
}
