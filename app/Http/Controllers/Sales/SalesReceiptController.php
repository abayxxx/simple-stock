<?php

// app/Http/Controllers/Sales/SalesReceiptController.php

namespace App\Http\Controllers\Sales;

use App\Exports\SalesReceiptsExport;
use App\Http\Controllers\Controller;
use App\Models\SalesReceipt;
use App\Models\SalesReceiptItem;
use App\Models\SalesInvoice;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeProfile;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class SalesReceiptController extends Controller
{

    // In SalesReceiptController

    public function datatable(Request $request)
    {
        $awal  = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $customerId = $request->customer_id;
        $collectorId = $request->collector_id;
        $query = SalesReceipt::with('customer', 'collector', 'receiptItems')
            ->orderByDesc('id');
        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
        }
        if ($customerId) {
            $query->where('company_profile_id', $customerId);
        }
        if ($collectorId) {
            $query->where('employee_id', $collectorId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->addColumn('collector', fn($r) => $r->collector->nama ?? '-')
            ->addColumn('total_faktur', fn($r) => number_format($r->total_faktur, 2, ',', '.'))
            ->addColumn('total_retur', fn($r) => number_format($r->total_retur, 2, ',', '.'))
            ->addColumn('aksi', function ($r) {
                return view('sales.receipts.partials.aksi', ['row' => $r])->render();
            })
            ->filterColumn('customer', function ($query, $keyword) {
                $query->whereHas('customer', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('collector', function ($query, $keyword) {
                $query->whereHas('collector', function ($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereDate('tanggal', 'like', "%{$keyword}%");
            })
            ->filterColumn('kode', function ($query, $keyword) {
                $query->where('kode', 'like', "%{$keyword}%");
            })
            ->filterColumn('total_faktur', function ($query, $keyword) {
                $query->whereRaw('total_faktur = ?', [$keyword]);
            })
            ->filterColumn('total_retur', function ($query, $keyword) {
                $query->whereRaw('total_retur = ?', [$keyword]);
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }


    public function index()
    {
        return view('sales.receipts.index');
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'supplier') // Hanya customer
            ->get();
        $employees = EmployeProfile::orderBy('nama')->get();

        // Hanya tarik faktur penjualan yang belum pernah diterima
        $availableInvoices = SalesInvoice::whereDoesntHave('receiptItems')
            ->get();

        return view('sales.receipts.create', compact('customers', 'employees', 'availableInvoices'));
    }

    public function tarikFakturOptions(Request $request)
    {
        $customerId = $request->input('customer_id');
        $query = SalesInvoice::where('sisa_tagihan', '>', 0)
            ->whereDoesntHave('receiptItems');

        if ($customerId) {
            $query->where('company_profile_id', $customerId);
        }

        $invoices = $query->with('customer')->get();

        return response()->json([
            'invoices' => $invoices
        ]);
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'kode' => 'nullable|string|max:50|unique:sales_receipts,kode',
                'auto_kode' => 'nullable|in:1',
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'employee_id' => 'required|exists:employe_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                'items.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
                'items.*.total_faktur' => 'required|numeric|min:0',
                'items.*.total_retur' => 'nullable|numeric|min:0',
                'items.*.sisa_tagihan' => 'nullable|numeric|min:0',
                'items.*.catatan' => 'nullable|string|max:255',
            ]);
        } catch (\Exception $e) {
            return back()->withInput()->withErrors(['error' => $e->getMessage()]);
        }



        if ($request->input('auto_kode')) {
            $data['kode'] = self::generateKode();
        } else {
            if (empty($data['kode']) || $data['kode'] == '(auto)') {
                return back()->withInput()->withErrors(['kode' => 'Kode harus diisi jika mode auto tidak dipilih']);
            }
        }

        DB::transaction(function () use ($data) {
            $items = $data['items'];
            unset($data['items']);

            $data['is_locked'] = true;
            $receipt = SalesReceipt::create($data + ['user_id' => auth()->id()]);

            foreach ($items as $item) {

                $receipt->receiptItems()->create($item);


                // Update SalesReceipt total_faktur and total_retur
                $receipt->total_faktur += $item['sisa_tagihan'];
                $receipt->total_retur += $item['total_retur'] ?? 0;
            }

            $receipt->save();
        });

        return redirect()->route('sales.receipts.index')->with('success', 'Tanda terima penjualan berhasil dibuat.');
    }

    protected static function generateKode()
    {
        $prefix = 'HR.' . date('ym') . '.';
        $last = SalesReceipt::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT) . '.' . date('Y');
    }

    public function show(SalesReceipt $receipt)
    {
        $receipt->load('customer', 'collector', 'receiptItems.invoice');
        return view('sales.receipts.show', compact('receipt'));
    }

    public function edit(SalesReceipt $receipt)
    {
        $receipt->load('customer', 'collector', 'receiptItems.invoice');
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'supplier')
            ->get();
        $employees = EmployeProfile::orderBy('nama')->get();

        // Hanya tarik faktur penjualan yang belum pernah diterima
        $availableInvoices = SalesInvoice::whereDoesntHave('receiptItems')
            ->get();

        return view('sales.receipts.edit', compact('receipt', 'customers', 'employees', 'availableInvoices'));
    }

    public function update(Request $request, SalesReceipt $receipt)
    {
        // Validasi dan update data tanda terima
        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sales_receipts,kode,' . $receipt->id,
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|exists:company_profiles,id',
            'employee_id' => 'required|exists:employe_profiles,id',
            'catatan' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.sales_invoice_id' => 'required|exists:sales_invoices,id',
            'items.*.total_faktur' => 'required|numeric|min:0',
            'items.*.total_retur' => 'nullable|numeric|min:0',
            'items.*.sisa_tagihan' => 'nullable|numeric|min:0',
            'items.*.catatan' => 'nullable|string|max:255',
        ]);

        // if ($request->input('auto_kode')) {
        //     $data['kode'] = self::generateKode();
        // } else {
        //     if (empty($data['kode']) || $data['kode'] == '(auto)') {
        //         return back()->withInput()->withErrors(['kode' => 'Kode harus diisi jika mode auto tidak dipilih']);
        //     }
        // }

        if (!isSuperAdmin()) {
            return redirect()->route('sales.receipts.index')
                ->with('error', 'Hanya super admin yang dapat mengubah tanda terima penjualan.');
        }

        DB::transaction(function () use ($data, $receipt) {
            // Hapus item lama
            $receipt->receiptItems()->delete();
            $receipt->total_faktur = 0;
            $receipt->total_retur = 0;

            // Update tanda terima
            $receipt->update($data + ['user_id' => auth()->id()]);

            // Tambah item baru
            foreach ($data['items'] as $item) {
                $receipt->receiptItems()->create($item);
                // Update total faktur dan retur
                $receipt->total_faktur += $item['sisa_tagihan'];
                $receipt->total_retur += $item['total_retur'] ?? 0;
            }

            $receipt->save();
        });

        return redirect()->route('sales.receipts.index')->with('success', 'Tanda terima penjualan berhasil diperbarui.');
    }


    public function destroy(SalesReceipt $receipt)
    {
        if (!isSuperAdmin()) {
            return redirect()->route('sales.receipts.index')
                ->with('error', 'Hanya super admin yang dapat menghapus tanda terima penjualan.');
        }

        // Hapus tanda terima dan relasinya
        $receipt->receiptItems()->delete();
        $receipt->delete();
        return redirect()->route('sales.receipts.index')->with('success', 'Tanda terima penjualan berhasil dihapus.');
    }

    public function print(SalesReceipt $receipt)
    {
        $receipt->load('customer', 'collector', 'receiptItems.invoice');
        return view('sales.receipts.print', compact('receipt'));
    }

    public function lock(SalesReceipt $receipt)
    {
        if (!isSuperAdmin()) {
            return redirect()->route('sales.receipts.show', $receipt->id)
                ->with('error', 'Hanya super admin yang dapat mengubah status kunci faktur.');
        }

        $receipt->is_locked = !$receipt->is_locked;
        $receipt->save();

        return redirect()->route('sales.receipts.show', $receipt->id)
            ->with('success', 'Status kunci faktur berhasil diubah.');
    }

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        // Ambil customer dan kolektor berdasarkan periode
        $query = SalesReceipt::query();
        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal, $akhir]);
        }

        $customerIds = $query->distinct()->pluck('company_profile_id');
        $collectorIds = $query->distinct()->pluck('employee_id');

        // Ambil data customer dan kolektor
        $customers = CompanyProfile::whereIn('id', $customerIds)->orderBy('name')->get(['id', 'name']);
        $collectors = EmployeProfile::whereIn('id', $collectorIds)->orderBy('nama')->get(['id', 'nama']);


        return response()->json([
            'customers' => $customers,
            'collectors' => $collectors,
        ]);
    }

   public function export(Request $request)
    {
        $awal        = $request->input('periode_awal');
        $akhir       = $request->input('periode_akhir');
        $customerId  = $request->input('customer_id');
        $collectorId = $request->input('collector_id');

        // Download with our columns
        return Excel::download(
            new SalesReceiptsExport($awal, $akhir, $customerId, $collectorId),
            'daftar_tanda_terima_penjualan.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        $awal        = $request->input('periode_awal');
        $akhir       = $request->input('periode_akhir');
        $customerId  = $request->input('customer_id');
        $collectorId = $request->input('collector_id');

        // Query: ONLY the fields we need
        $rows = DB::table('sales_receipts as sr')
            ->leftJoin('company_profiles as cp', 'cp.id', '=', 'sr.company_profile_id')
            ->leftJoin('employe_profiles as ep', 'ep.id', '=', 'sr.employee_id') // table/col per your code
            ->leftJoin('sales_receipt_items as sri', 'sri.sales_receipt_id', '=', 'sr.id')
            ->when($awal && $akhir, fn($q) => $q->whereBetween('sr.tanggal', [$awal.' 00:00:00', $akhir.' 23:59:59']))
            ->when($customerId,  fn($q) => $q->where('sr.company_profile_id', $customerId))
            ->when($collectorId, fn($q) => $q->where('sr.employee_id',      $collectorId))
            ->groupBy('sr.id', 'sr.tanggal', 'sr.kode', 'cp.name', 'ep.nama', 'sr.total_faktur', 'sr.total_retur')
            ->orderBy('sr.tanggal')
            ->get([
                'sr.tanggal',
                'sr.kode',
                DB::raw('COALESCE(cp.name, "-")  as customer_name'),
                DB::raw('COALESCE(ep.nama, "-")  as collector_name'),
                DB::raw('COUNT(sri.id)           as jml_faktur'),
                DB::raw('sr.total_faktur as grand_total'),
            ]);

        $periodeText = ($awal && $akhir)
            ? date('d M Y', strtotime($awal)).' s/d '.date('d M Y', strtotime($akhir))
            : '-';

        $total = $rows->sum('grand_total');

        $pdf = Pdf::loadView('sales.receipts.export_pdf', [
            'rows'        => $rows,
            'periodeText' => $periodeText,
            'total'       => $total,
        ])->setPaper('a4', 'portrait'); // change to 'landscape' if you prefer

        return $pdf->download('daftar_tanda_terima_penjualan.pdf');
    }
}
