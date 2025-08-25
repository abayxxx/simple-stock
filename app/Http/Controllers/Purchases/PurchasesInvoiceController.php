<?php

// app/Http/Controllers/Purchases/PurchasesInvoiceController.php

namespace App\Http\Controllers\Purchases;

use App\Http\Controllers\Controller;
use App\Models\PurchasesInvoice;
use App\Models\Product;
use App\Models\CompanyBranch;
use App\Models\CompanyProfile;
use App\Models\Stock;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class PurchasesInvoiceController extends Controller
{
    public function datatable(Request $request)
    {
        $awal = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $supplierId    = $request->supplier_id;     // from #filter_customer

        $query = PurchasesInvoice::with('supplier')->orderByDesc('id');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
        }

        if ($supplierId) {
            $query->where('company_profile_id', $supplierId);
        }

        return DataTables::of($query)
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('supplier', fn($r) => $r->supplier->name ?? '-')
            ->editColumn('grand_total', fn($r) => number_format($r->grand_total, 2, ',', '.'))
            ->editColumn('total_retur', fn($r) => number_format($r->total_retur, 2, ',', '.'))
            ->editColumn('total_bayar', fn($r) => number_format($r->total_bayar, 2, ',', '.'))
            ->editColumn('sisa_tagihan', fn($r) => number_format($r->sisa_tagihan, 2, ',', '.'))
            ->editColumn('created_at', fn($r) => $r->created_at->format('d M Y H:i'))
            ->editColumn('tgl_pembayaran', fn($r) => $r->paymentItems->count() > 0 ? $r->latestPayment()->first()->created_at->format('d M Y H:i') : '-')
            ->addColumn('aksi', function ($r) {
                return view('purchases.invoices.partials.aksi', ['row' => $r])->render();
            })
            ->filterColumn('supplier', function ($query, $keyword) {
                $query->whereHas('supplier', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal', function ($q, $keyword) {
                // match 'YYYY-MM-DD' or Indonesian formatted text
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('tanggal', 'like', "%{$keyword}%")
                        ->orWhereRaw("DATE_FORMAT(tanggal, '%d %b %Y') like ?", ["%{$keyword}%"]);
                });
            })
            ->filterColumn('created_at', function ($q, $keyword) {
                $q->whereRaw("DATE_FORMAT(created_at, '%d %b %Y %H:%i') like ?", ["%{$keyword}%"]);
            })
            ->filterColumn('tgl_pembayaran', function ($q, $keyword) {
                $q->whereHas('paymentItems.payment', function ($qq) use ($keyword) {
                    $qq->whereRaw("DATE_FORMAT(created_at, '%d %b %Y %H:%i') like ?", ["%{$keyword}%"]);
                });
                // ^ adjust relation path if needed (e.g. latestPayment relation)
            })
            // 🔎 Make formatted numbers searchable
            ->filterColumn('grand_total', function ($q, $keyword) {
                $q->whereRaw('CAST(grand_total AS CHAR) like ?', ["%{$keyword}%"]);
            })
            ->filterColumn('total_retur', function ($q, $keyword) {
                $q->whereRaw('CAST(total_retur AS CHAR) like ?', ["%{$keyword}%"]);
            })
            ->filterColumn('total_bayar', function ($q, $keyword) {
                $q->whereRaw('CAST(total_bayar AS CHAR) like ?', ["%{$keyword}%"]);
            })
            ->filterColumn('sisa_tagihan', function ($q, $keyword) {
                $q->whereRaw('CAST(sisa_tagihan AS CHAR) like ?', ["%{$keyword}%"]);
            })
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }

    public function index()
    {
        return view('purchases.invoices.index');
    }

    public function create()
    {
        $suppliers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'customer') // Ensure not a customer
            ->get();
        $branches = CompanyBranch::orderBy('name')->get();
        // Load products for dropdown
        return view('purchases.invoices.create', compact('suppliers', 'branches'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:purchases_invoices,kode',
            'auto_kode' => 'nullable|in:1',
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer|exists:company_profiles,id',
            'term' => 'nullable|string|max:50',
            'no_order' => 'nullable|string|max:100',
            'is_tunai' => 'boolean',
            'is_include_ppn' => 'boolean',
            'is_received' => 'boolean',
            'catatan' => 'nullable|string',
            'diskon_faktur' => 'nullable|numeric',
            'diskon_ppn' => 'nullable|numeric',
            'subtotal' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total_retur' => 'nullable|numeric',
            'total_bayar' => 'nullable|numeric',
            'sisa_tagihan' => 'nullable|numeric',
            'jatuh_tempo' => 'nullable|date',
            // Items array
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.no_seri' => 'nullable|string|max:50',
            'items.*.tanggal_expired' => 'nullable|string',
            'items.*.diskon_1_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_1_rupiah' => 'nullable|numeric|min:0',
            'items.*.diskon_2_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_2_rupiah' => 'nullable|numeric|min:0',
            'items.*.diskon_3_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_3_rupiah' => 'nullable|numeric|min:0',
            'items.*.sub_total_sblm_disc' => 'nullable|numeric',
            'items.*.total_diskon_item' => 'nullable|numeric',
            'items.*.sub_total_sebelum_ppn' => 'nullable|numeric',
            'items.*.ppn_persen' => 'nullable|numeric',
            'items.*.sub_total_setelah_disc' => 'nullable|numeric',
            'items.*.catatan' => 'nullable|string',
        ]);

        if ($request->input('auto_kode')) {
            $data['kode'] = self::generateKode();
        } else {
            if (!$data['kode'] || $data['kode'] == '(auto)') {
                return back()->withInput()->withErrors(['kode' => 'Nomor Invoice harus diisi jika mode auto tidak dipilih']);
            }
        }

        // Set defaults
        $data['diskon_faktur'] = $data['diskon_faktur'] ?? 0;
        $data['diskon_ppn'] = $data['diskon_ppn'] ?? 0;

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use (&$invoice, $data, $items) {
            $subtotal = 0;
            foreach ($items as $it) {
                $line = (float)$it['qty'] * (float)$it['harga_satuan'];
                $p1 = $it['diskon_1_persen'] ?? 0;
                if ($p1) $line -= $line * ($p1 / 100);
                $p2 = $it['diskon_2_persen'] ?? 0;
                if ($p2) $line -= $line * ($p2 / 100);
                $p3 = $it['diskon_3_persen'] ?? 0;
                if ($p3) $line -= $line * ($p3 / 100);
                $r1 = $it['diskon_1_rupiah'] ?? 0;
                $line -= $r1;
                $r2 = $it['diskon_2_rupiah'] ?? 0;
                $line -= $r2;
                $r3 = $it['diskon_3_rupiah'] ?? 0;
                $line -= $r3;
                if ($line < 0) $line = 0;
                $subtotal += $line;
            }

            $grand = max(0, $subtotal - ($data['diskon_faktur'] ?? 0));
            if (!empty($data['diskon_ppn'])) {
                $grand += $grand * ($data['diskon_ppn'] / 100);
            }

            $header = $data + [
                'subtotal'     => $subtotal,
                'grand_total'  => $grand,
                'total_retur'  => 0,
                'total_bayar'  => 0,
                'sisa_tagihan' => $grand,
                'user_id'      => auth()->id(),
            ];

            $invoice = PurchasesInvoice::create($header);
            foreach ($items as $item) {

                // Create purchases invoice item
                $invoice->items()->create($item);

                // STOCK IN: Create stock entry for each item
                Stock::create([
                    'product_id'      => $item['product_id'],
                    'type'            => 'in', // STOCK IN for purchases
                    'jumlah'          => $item['qty'],
                    'no_seri'         => $item['no_seri'] ?? null,
                    'tanggal_expired' => $item['tanggal_expired'] ?? null,
                    'harga_net'       => $item['harga_satuan'],
                    'catatan'         => "Pembelian (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'       => 0, // Can be updated after as needed
                    'created_at'      => $invoice->tanggal . ' ' . now()->format('H:i:s'), // Align with invoice time
                ]);
                // 4. Update sisa stok untuk batch ini
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        return redirect()->route('purchases.invoices.index')->with('success', 'Purchases Invoice berhasil dibuat.');
    }

    public function show(PurchasesInvoice $invoice)
    {
        $invoice->load('items.product', 'supplier', 'user');
        return view('purchases.invoices.show', compact('invoice'));
    }

    public function edit(PurchasesInvoice $invoice)
    {
        $suppliers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'customer') // Ensure not a customer
            ->get();
        // load only products in purchases invoice
        $products = Product::whereIn('id', $invoice->items->pluck('product_id'))->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoice->load('items');
        return view('purchases.invoices.edit', compact('invoice', 'suppliers', 'branches', 'products'));
    }

    public function update(Request $request, PurchasesInvoice $invoice)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer|exists:company_profiles,id',
            'term' => 'nullable|string|max:50',
            'no_order' => 'nullable|string|max:100',
            'is_tunai' => 'boolean',
            'is_include_ppn' => 'boolean',
            'is_received' => 'boolean',
            'catatan' => 'nullable|string',
            'diskon_faktur' => 'nullable|numeric',
            'diskon_ppn' => 'nullable|numeric',
            'subtotal' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total_retur' => 'nullable|numeric',
            'total_bayar' => 'nullable|numeric',
            'sisa_tagihan' => 'nullable|numeric',
            'jatuh_tempo' => 'nullable|date',
            // Items array
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.harga_satuan' => 'required|numeric|min:0',
            'items.*.no_seri' => 'nullable|string|max:50',
            'items.*.tanggal_expired' => 'nullable|string',
            'items.*.diskon_1_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_1_rupiah' => 'nullable|numeric|min:0',
            'items.*.diskon_2_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_2_rupiah' => 'nullable|numeric|min:0',
            'items.*.diskon_3_persen' => 'nullable|numeric|min:0',
            'items.*.diskon_3_rupiah' => 'nullable|numeric|min:0',
            'items.*.sub_total_sblm_disc' => 'nullable|numeric',
            'items.*.total_diskon_item' => 'nullable|numeric',
            'items.*.sub_total_sebelum_ppn' => 'nullable|numeric',
            'items.*.ppn_persen' => 'nullable|numeric',
            'items.*.sub_total_setelah_disc' => 'nullable|numeric',
            'items.*.catatan' => 'nullable|string',
        ]);



        // Set defaults
        $data['diskon_faktur'] = $data['diskon_faktur'] ?? 0;
        $data['diskon_ppn'] = $data['diskon_ppn'] ?? 0;

        $items = $data['items'];
        unset($data['items']);

        $originalKode = $invoice->getOriginal('kode');


        DB::transaction(function () use ($invoice, $data, $items, $originalKode) {

            // Cek apakah faktur ini ada retur
            if ($invoice->retur()->exists()) {
                throw ValidationException::withMessages([
                    'error' => 'Faktur ini sudah memiliki retur. Silakan hapus retur terlebih dahulu sebelum mengubah faktur.'
                ]);
            }
            if ($invoice->paymentItems()->exists()) {
                throw ValidationException::withMessages(['error' => 'Faktur sudah memiliki pembayaran. Batalkan pembayaran terlebih dahulu.']);
            }

            // ✅ Update header sekali saja
            $invoice->update($data + ['user_id' => auth()->id()]);


            // 1) Hapus stok 'in' lama (pakai kode LAMA & LIKE agar kebal perubahan catatan item)
            $oldItems = $invoice->items()->with(['product'])->get(); // ambil dulu sebelum delete

            foreach ($oldItems as $old) {
                $query = Stock::where('product_id', $old->product_id)
                    ->where('type', 'in')
                    ->where(function ($q) use ($old) {
                        // filter batch bila ada
                        // $q->when($old->no_seri, fn($qq) => $qq->where('no_seri', $old->no_seri))
                        // ->when($old->tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $old->tanggal_expired));
                    })
                    ->where('catatan', 'like', "Pembelian (Faktur: {$originalKode})%");

                $query->delete();

                // recompute sisa stok per-batch
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }

            // 2) Hapus semua item lama
            $invoice->items()->delete();


            // 3) Tambah item baru + stok 'in' baru
            foreach ($items as $item) {

                $key = "{$item['product_id']}";
                $invoiceItem = $invoice->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'in', // STOCK IN for purchases
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Pembelian (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                    'created_at'       => $invoice->tanggal . ' ' . now()->format('H:i:s'), // Align with invoice time
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });
        return redirect()->route('purchases.invoices.index')->with('success', 'Purchases Invoice berhasil diperbarui.');
    }


    public function destroy(PurchasesInvoice $invoice)
    {

        // Hapus stok 'in' terkait
        $items = $invoice->items()->with(['product'])->get();
        foreach ($items as $item) {
            $stocks = Stock::where('product_id', $item->product_id)
                ->where('type', 'in')
                // ->when($item->no_seri, fn($q) => $q->where('no_seri', $item->no_seri))
                // ->when($item->tanggal_expired, fn($q) => $q->where('tanggal_expired', $item->tanggal_expired))
                ->where('catatan', 'like', "Pembelian (Faktur: {$invoice->kode})%")
                ->delete();
            // recompute sisa stok per-batch
            self::updateAllSisaStok($item->product_id, $item->no_seri, $item->tanggal_expired);
        }
        //Hapus semua item terkait
        $invoice->items()->delete();
        //Hapus faktur
        $invoice->delete();
        return redirect()->route('purchases.invoices.index')->with('success', 'Faktur Pembelian berhasil dihapus.');
    }

    protected static function generateKode()
    {
        $prefix = 'PI.' . date('dm') . '.';
        $last = PurchasesInvoice::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT) . '.' . date('y');
    }

    public function calculateSisaStok($product_id)
    {

        $in     = Stock::where('product_id', $product_id)->where('type', 'in')->sum('jumlah');
        $out    = Stock::where('product_id', $product_id)->where('type', 'out')->sum('jumlah');
        $destroy = Stock::where('product_id', $product_id)->where('type', 'destroy')->sum('jumlah');
        return $in - $out - $destroy;
    }

    private function getSisaStokBatch($product_id, $no_seri = null, $tanggal_expired = null, $lock = false)
    {
        $q = Stock::where('product_id', $product_id);
        // ->when($no_seri, fn($qq) => $qq->where('no_seri', $no_seri))
        // ->when($tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $tanggal_expired));

        if ($lock) $q->lockForUpdate();

        $rows = $q->get(); // biar konsisten saat lock
        $in = $rows->where('type', 'in')->sum('jumlah');
        $out = $rows->where('type', 'out')->sum('jumlah');
        $destroy = $rows->where('type', 'destroy')->sum('jumlah');
        return $in - $out - $destroy;
    }

    /**
     * Update semua sisa_stok di tabel Stock untuk 1 batch
     */
    public static function updateAllSisaStok($product_id, $no_seri = null, $tanggal_expired = null)
    {
        $query = Stock::query()
            ->where('product_id', $product_id);
        // ->when($no_seri, fn($qq) => $qq->where('no_seri', $no_seri))
        // ->when($tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $tanggal_expired));

        $stocks = $query->orderBy('id')->lockForUpdate()->get();
        $runningSisa = 0;
        foreach ($stocks as $stock) {
            if ($stock->type === 'in') $runningSisa += $stock->jumlah;
            elseif (in_array($stock->type, ['out', 'destroy'])) $runningSisa -= $stock->jumlah;

            $stock->sisa_stok = $runningSisa;
            $stock->subtotal = $stock->jumlah * $stock->harga_net;
            $stock->save();
        }
    }


    // Print
    public function print(PurchasesInvoice $invoice)
    {
        $invoice->load('items.product', 'supplier', 'user');
        return view('purchases.invoices.print', compact('invoice'));
    }

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        $base = PurchasesInvoice::query();
        if ($awal  && $akhir) {
            $base->whereBetween('tanggal', [$awal, $akhir]);
        }

        // Distinct keys from invoices in range
        $supplierIds   = (clone $base)->whereNotNull('company_profile_id')->distinct()->pluck('company_profile_id');


        // Fetch display names
        $suppliers   = CompanyProfile::whereIn('id', $supplierIds)->orderBy('name')->get(['id', 'name']);


        return response()->json([
            'suppliers'    => $suppliers,
        ]);
    }
}
