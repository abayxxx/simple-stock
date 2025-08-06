<?php

// app/Http/Controllers/Sales/SalesReceiptController.php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\SalesReceipt;
use App\Models\SalesReceiptItem;
use App\Models\SalesInvoice;
use App\Models\CompanyProfile;
use App\Models\Employee;
use App\Models\EmployeProfile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesReceiptController extends Controller
{

    // In SalesReceiptController

    public function datatable(Request $request)
    {
        $awal  = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $query = SalesReceipt::with('customer', 'collector', 'receiptItems')
            ->orderByDesc('tanggal');
        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal, $akhir]);
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
            ->rawColumns(['aksi'])
            ->make(true);
    }


    public function index()
    {
        $receipts = SalesReceipt::with('customer', 'collector')->orderByDesc('tanggal')->paginate(20);
        return view('sales.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')->get();
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

            $receipt = SalesReceipt::create($data + ['user_id' => auth()->id()]);

            foreach ($items as $item) {
                $receipt->receiptItems()->create($item);

                // Update SalesReceipt total_faktur and total_retur
                $receipt->total_faktur += $item['total_faktur'];
                $receipt->total_retur += $item['total_retur'] ?? 0;
            }

            $receipt->save();
        });

        return redirect()->route('sales.receipts.index')->with('success', 'Tanda terima penjualan berhasil dibuat.');
    }

    protected static function generateKode()
    {
        $prefix = 'SRc.' . date('ym') . '.';
        $last = SalesReceipt::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    public function show(SalesReceipt $receipt)
    {
        $receipt->load('customer', 'collector', 'receiptItems.invoice');
        return view('sales.receipts.show', compact('receipt'));
    }

    public function edit(SalesReceipt $receipt)
    {
        $receipt->load('customer', 'collector', 'receiptItems.invoice');
        $customers = CompanyProfile::orderBy('name')->get();
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
                $receipt->total_faktur += $item['total_faktur'];
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
       if(!isSuperAdmin()) {
            return redirect()->route('sales.receipts.show', $receipt->id)
                ->with('error', 'Hanya super admin yang dapat mengubah status kunci faktur.');
        }

        $receipt->is_locked = !$receipt->is_locked;
        $receipt->save();

        return redirect()->route('sales.receipts.show', $receipt->id)
            ->with('success', 'Status kunci faktur berhasil diubah.');
    }
}
