<?php

// app/Http/Controllers/Sales/SalesPaymentController.php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\SalesInvoice;
use App\Models\SalesPayment;
use App\Models\SalesReturn;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SalesPaymentController extends Controller
{
    public function index()
    {
        return view('sales.payments.index');
    }

    public function datatable(Request $request)
    {
        $query = SalesPayment::with('customer', 'user')->orderByDesc('tanggal');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->addColumn('jumlah_nota', fn($r) => $r->items->count())
            ->addColumn('total_bayar', fn($r) => number_format($r->items->sum('sub_total'), 2, ',', '.'))
            ->addColumn('aksi', function ($r) {
                return view('sales.payments.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')->get();
        return view('sales.payments.create', compact('customers'));
    }

    public function tarikNotaOptions(Request $request)
    {
        $customerId = $request->input('company_profile_id');

        // FAKTUR: Yang masih ada sisa tagihan
        $invoices = SalesInvoice::where('sisa_tagihan', '>', 0)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('company_profile_id', $customerId);
            })
            ->get();

        // RETUR: Yang belum terpakai (atau bisa buat query khusus sesuai bisnis proses)
        $returns = SalesReturn::where('grand_total', '>', 0)
            ->when($customerId, function ($q) use ($customerId) {
                $q->where('company_profile_id', $customerId);
            })
            ->get();

        return response()->json([
            'invoices' => $invoices,
            'returns' => $returns,
        ]);
    }

    public function store(Request $request)
    {
        try {
            //code...
            $data = $request->validate([
                'kode' => 'nullable|string|max:50|unique:sales_payments,kode',
                'auto_kode' => 'nullable|in:1',
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                // Items detail
                'items.*.tipe_nota' => 'required|in:FAKTUR,RETUR',
                'items.*.sales_invoice_id' => 'nullable|exists:sales_invoices,id',
                'items.*.sales_return_id' => 'nullable|exists:sales_returns,id',
                'items.*.nilai_nota' => 'nullable|numeric',
                'items.*.sisa' => 'nullable|numeric',
                'items.*.tunai' => 'nullable|numeric',
                'items.*.bank' => 'nullable|numeric',
                'items.*.giro' => 'nullable|numeric',
                'items.*.cndn' => 'nullable|numeric',
                'items.*.retur' => 'nullable|numeric',
                'items.*.panjar' => 'nullable|numeric',
                'items.*.lainnya' => 'nullable|numeric',
                'items.*.sub_total' => 'nullable|numeric',
                'items.*.pot_ke_no' => 'nullable|string',
                'items.*.catatan' => 'nullable|string|max:255',
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->withErrors($th->getMessage())->withInput();
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

            $payment = SalesPayment::create($data + ['user_id' => auth()->id()]);

            foreach ($items as $item) {
                $payment->items()->create($item);

                // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
                if ($item['tipe_nota'] === 'FAKTUR' && !empty($item['sales_invoice_id'])) {
                    $invoice = SalesInvoice::find($item['sales_invoice_id']);
                    $invoice->total_bayar += $item['sub_total'] ?? 0;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $invoice->save();
                }
                if ($item['tipe_nota'] === 'RETUR' && !empty($item['sales_return_id'])) {
                    // Update the return if needed
                    $return = SalesReturn::find($item['sales_return_id']);
                    $return->total_bayar += $item['sub_total'] ?? 0;
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $return->save();
                }
            }
        });

        return redirect()->route('sales.payments.index')->with('success', 'Pembayaran penjualan berhasil dibuat.');
    }

    protected static function generateKode()
    {
        $prefix = 'PP.' . date('ym') . '.';
        $last = SalesPayment::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    public function show(SalesPayment $payment)
    {
        $payment->load('customer', 'items.invoice', 'items.return');
        return view('sales.payments.show', compact('payment'));
    }

    public function edit(SalesPayment $payment)
    {
        $payment->load('customer', 'items.invoice', 'items.return');
        $customers = CompanyProfile::orderBy('name')->get();
        return view('sales.payments.edit', compact('payment', 'customers'));
    }

    public function update(Request $request, SalesPayment $payment)
    {
        try {
            $data = $request->validate([
                'kode' => 'nullable|string|max:50|unique:sales_payments,kode,' . $payment->id,
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                // Items detail
                'items.*.tipe_nota' => 'required|in:FAKTUR,RETUR',
                'items.*.sales_invoice_id' => 'nullable|exists:sales_invoices,id',
                'items.*.sales_return_id' => 'nullable|exists:sales_returns,id',
                'items.*.nilai_nota' => 'nullable|numeric',
                'items.*.sisa' => 'nullable|numeric',
                'items.*.tunai' => 'nullable|numeric',
                'items.*.bank' => 'nullable|numeric',
                'items.*.giro' => 'nullable|numeric',
                'items.*.cndn' => 'nullable|numeric',
                'items.*.retur' => 'nullable|numeric',
                'items.*.panjar' => 'nullable|numeric',
                'items.*.lainnya' => 'nullable|numeric',
                'items.*.sub_total' => 'nullable|numeric',
                'items.*.pot_ke_no' => 'nullable|string',
                'items.*.catatan' => 'nullable|string|max:255',
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }

        DB::transaction(function () use ($data, $payment) {
            $payment->update($data + ['user_id' => auth()->id()]);

            // Hapus semua item lama
            $payment->items()->delete();

            // Tambah item baru
            foreach ($data['items'] as $item) {
                $payment->items()->create($item);

                // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
                if ($item['tipe_nota'] === 'FAKTUR' && !empty($item['sales_invoice_id'])) {
                    $invoice = SalesInvoice::find($item['sales_invoice_id']);
                    $invoice->total_bayar += $item['sub_total'] ?? 0;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $invoice->save();
                }
                if ($item['tipe_nota'] === 'RETUR' && !empty($item['sales_return_id'])) {
                    // Update the return if needed
                    $return = SalesReturn::find($item['sales_return_id']);
                    $return->total_bayar += $item['sub_total'] ?? 0;
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $return->save();
                }

                // Update the payment total (optional, add your logic here)
                $payment->total_bayar += $item['sub_total'] ?? 0;
                $payment->save();
            }
        });
        return redirect()->route('sales.payments.index')->with('success', 'Pembayaran penjualan berhasil diperbarui.');
    }
    public function destroy(SalesPayment $payment)
    {
        DB::transaction(function () use ($payment) {

            // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
            foreach ($payment->items as $item) {
                if ($item->tipe_nota === 'FAKTUR' && !empty($item->sales_invoice_id)) {
                    $invoice = SalesInvoice::find($item->sales_invoice_id);
                    $invoice->total_bayar -= $item->sub_total;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $item->sub_total);
                    $invoice->save();
                }
                if ($item->tipe_nota === 'RETUR' && !empty($item->sales_return_id)) {
                    $return = SalesReturn::find($item->sales_return_id);
                    $return->total_bayar -= $item->sub_total;
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan + $item->sub_total);
                    $return->save();
                }
            }

            // Hapus semua item terkait
            $payment->items()->delete();

            // Hapus pembayaran
            $payment->delete();
        });

        return redirect()->route('sales.payments.index')->with('success', 'Pembayaran penjualan berhasil dihapus.');
    }
}
