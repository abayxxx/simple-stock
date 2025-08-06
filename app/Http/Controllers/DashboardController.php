<?php


namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;
use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;

class DashboardController extends Controller
{
    public function index()
    {
        $today = Carbon::today();
        $month = $today->format('m');
        $year = $today->format('Y');

        // Cache each statistic for 5 minutes
        $totalCustomer = Cache::remember('dashboard_total_customer', 300, function () {
            return CompanyProfile::count();
        });

        $totalProduk = Cache::remember('dashboard_total_produk', 300, function () {
            return Product::count();
        });

        $penjualanHariIni = Cache::remember("dashboard_penjualan_hari_ini_$today", 300, function () use ($today) {
            return SalesInvoice::whereDate('tanggal', $today)->sum('grand_total');
        });

        $tandaTerimaHariIni = Cache::remember("dashboard_tanda_terima_hari_ini_$today", 300, function () use ($today) {
            // Example: replace with your actual logic
            return \App\Models\SalesReceipt::whereDate('tanggal', $today)->count();
        });

        // ** Jatuh tempo per customer (limit 10 for dashboard) **
        $fakturJatuhTempo = Cache::remember("dashboard_faktur_jatuh_tempo_$today", 300, function () use ($today) {
            return SalesInvoice::with('customer')
                ->whereDate('jatuh_tempo', '<=', $today)
                ->where('sisa_tagihan', '>', 0)
                ->orderBy('jatuh_tempo')
                ->limit(10)
                ->get();
        });

        // ** Top 20 produk bulan ini **
        $topProdukBulan = Cache::remember("dashboard_top_produk_{$year}_{$month}", 300, function () use ($month, $year) {
            return SalesInvoiceItem::selectRaw('product_id, SUM(qty) as total_unit')
                ->whereMonth('created_at', $month)
                ->whereYear('created_at', $year)
                ->groupBy('product_id')
                ->orderByDesc('total_unit')
                ->with('product')
                ->limit(20)
                ->get();
        });

        // ** Data penjualan bulanan untuk grafik **
        $chartData = Cache::remember("dashboard_chart_data_{$year}_{$month}", 300, function () use ($month, $year) {
            $chart = [];
            $days = Carbon::create($year, $month, 1)->daysInMonth;
            for ($d = 1; $d <= $days; $d++) {
                $date = Carbon::create($year, $month, $d)->toDateString();
                $total = SalesInvoice::whereDate('tanggal', $date)->sum('grand_total');
                $chart[] = [
                    'tanggal' => $d,
                    'total' => (float)$total
                ];
            }
            return $chart;
        });

        // ** Example for call activity: replace with your own logic **

        // From how many customers were ordered today
        $totalCallCustomerHariIni = Cache::remember("dashboard_total_call_customer_hari_ini_$today", 300, function () use ($today) {
            return SalesInvoice::whereDate('tanggal', $today)->distinct('company_profile_id')->count('company_profile_id');
        });

        // From how many products were ordered today not using distinct
        $totalCallProdukHariIni = Cache::remember("dashboard_total_call_produk_hari_ini_$today", 300, function () use ($today) {
            return SalesInvoiceItem::whereHas('invoice', function ($query) use ($today) {
                $query->whereDate('tanggal', $today);
            })->count();
        });

        return view('dashboard.index', compact(
            'totalCustomer',
            'totalProduk',
            'penjualanHariIni',
            'tandaTerimaHariIni',
            'fakturJatuhTempo',
            'topProdukBulan',
            'chartData',
            'totalCallCustomerHariIni',
            'totalCallProdukHariIni',
            'today'
        ));
    }
}
