<?php

// app/Http/Controllers/Purchases/PurchasesPaymentController.php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\CompanyProfile;
use App\Models\PurchasesPayment;
use App\Models\PurchasesPaymentItem;
use App\Models\PurchasesInvoice;
use App\Models\PurchasesReturn;
use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class PurchasesPaymentController extends Controller
{
    public function index()
    {
        return view('purchases.payments.index');
    }

    public function datatable(Request $request)
    {
        $query = PurchasesPayment::with('supplier', 'user')->orderByDesc('tanggal');

        return DataTables::of($query)
            ->addIndexColumn()
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('supplier', fn($r) => $r->supplier->name ?? '-')
            ->addColumn('jumlah_nota', fn($r) => $r->items->count())
            ->addColumn('total_bayar', fn($r) => number_format($r->items->sum('sub_total'), 2, ',', '.'))
            ->addColumn('aksi', function ($r) {
                return view('purchases.payments.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create()
    {
        $suppliers = CompanyProfile::orderBy('name')->get();
        return view('purchases.payments.create', compact('suppliers'));
    }

    public function tarikNotaOptions(Request $request)
    {
        $supplierId = $request->input('company_profile_id');

        // FAKTUR: Yang masih ada sisa tagihan
        $invoices = PurchasesInvoice::where('sisa_tagihan', '>', 0)
            ->when($supplierId, function ($q) use ($supplierId) {
                $q->where('company_profile_id', $supplierId);
            })
            ->get();

        // RETUR: Yang belum terpakai (atau bisa buat query khusus sesuai bisnis proses)
        $returns = PurchasesReturn::where('grand_total', '>', 0)
            ->when($supplierId, function ($q) use ($supplierId) {
                $q->where('company_profile_id', $supplierId);
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
                'kode' => 'nullable|string|max:50|unique:purchases_payments,kode',
                'auto_kode' => 'nullable|in:1',
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                // Items detail
                'items.*.tipe_nota' => 'required|in:FAKTUR,RETUR',
                'items.*.purchases_invoice_id' => 'nullable|exists:purchases_invoices,id',
                'items.*.purchases_return_id' => 'nullable|exists:purchases_returns,id',
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

            $payment = PurchasesPayment::create($data + ['user_id' => auth()->id()]);

            foreach ($items as $item) {
                $payment->items()->create($item);

                // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
                if ($item['tipe_nota'] === 'FAKTUR' && !empty($item['purchases_invoice_id'])) {
                    $invoice = PurchasesInvoice::find($item['purchases_invoice_id']);
                    $invoice->total_bayar += $item['sub_total'] ?? 0;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $invoice->save();
                }
                if ($item['tipe_nota'] === 'RETUR' && !empty($item['purchases_return_id'])) {
                    // Update the return if needed
                    $return = PurchasesReturn::find($item['purchases_return_id']);
                    $return->total_bayar += $item['sub_total'] ?? 0;
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $return->save();
                }
            }
        });

        return redirect()->route('purchases.payments.index')->with('success', 'Pembayaran pembelian berhasil dibuat.');
    }

    protected static function generateKode()
    {
        $prefix = 'PP.' . date('ym') . '.';
        $last = PurchasesPayment::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    public function show(PurchasesPayment $payment)
    {
        $payment->load('supplier', 'items.invoice', 'items.return');
        return view('purchases.payments.show', compact('payment'));
    }

    public function edit(PurchasesPayment $payment)
    {
        $payment->load('supplier', 'items.invoice', 'items.return');
        $suppliers = CompanyProfile::orderBy('name')->get();
        return view('purchases.payments.edit', compact('payment', 'suppliers'));
    }

    public function update(Request $request, PurchasesPayment $payment)
    {
        try {
            $data = $request->validate([
                'kode' => 'nullable|string|max:50|unique:purchases_payments,kode,' . $payment->id,
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|exists:company_profiles,id',
                'catatan' => 'nullable|string',
                'items' => 'required|array|min:1',
                // Items detail
                'items.*.tipe_nota' => 'required|in:FAKTUR,RETUR',
                'items.*.purchases_invoice_id' => 'nullable|exists:purchases_invoices,id',
                'items.*.purchases_return_id' => 'nullable|exists:purchases_returns,id',
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
                if ($item['tipe_nota'] === 'FAKTUR' && !empty($item['purchases_invoice_id'])) {
                    $invoice = PurchasesInvoice::find($item['purchases_invoice_id']);
                    $invoice->total_bayar += $item['sub_total'] ?? 0;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $invoice->save();
                }
                if ($item['tipe_nota'] === 'RETUR' && !empty($item['purchases_return_id'])) {
                    // Update the return if needed
                    $return = PurchasesReturn::find($item['purchases_return_id']);
                    $return->total_bayar += $item['sub_total'] ?? 0;
                    $return->sisa_tagihan = max(0, $return->sisa_tagihan - ($item['sub_total'] ?? 0));
                    $return->save();
                }

                // Update the payment total (optional, add your logic here)
                $payment->total_bayar += $item['sub_total'] ?? 0;
                $payment->save();
            }
        });
        return redirect()->route('purchases.payments.index')->with('success', 'Pembayaran pembelian berhasil diperbarui.');
    }
    public function destroy(PurchasesPayment $payment)
    {
        DB::transaction(function () use ($payment) {

            // Update invoice or return sisa_tagihan/grand_total (optional, add your logic here)
            foreach ($payment->items as $item) {
                if ($item->tipe_nota === 'FAKTUR' && !empty($item->purchases_invoice_id)) {
                    $invoice = PurchasesInvoice::find($item->purchases_invoice_id);
                    $invoice->total_bayar -= $item->sub_total;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $item->sub_total);
                    $invoice->save();
                }
                if ($item->tipe_nota === 'RETUR' && !empty($item->purchases_return_id)) {
                    $return = PurchasesReturn::find($item->purchases_return_id);
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

        return redirect()->route('purchases.payments.index')->with('success', 'Pembayaran pembelian berhasil dihapus.');
    }
}
