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
use Yajra\DataTables\Facades\DataTables;

class PurchasesInvoiceController extends Controller
{
    public function datatable(Request $request)
    {
        $awal = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $query = PurchasesInvoice::with('supplier');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal, $akhir]);
        }

        return DataTables::of($query)
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('supplier', fn($r) => $r->supplier->name ?? '-')
            ->editColumn('grand_total', fn($r) => number_format($r->grand_total, 2, ',', '.'))
            ->editColumn('total_retur', fn($r) => number_format($r->total_retur, 2, ',', '.'))
            ->editColumn('total_bayar', fn($r) => number_format($r->total_bayar, 2, ',', '.'))
            ->editColumn('sisa_tagihan', fn($r) => number_format($r->sisa_tagihan, 2, ',', '.'))
            ->editColumn('created_at', fn($r) => $r->created_at->format('d M Y H:i'))
            ->addColumn('aksi', function ($r) {
                return view('purchases.invoices.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }

    public function index()
    {
        $invoices = PurchasesInvoice::with('supplier', 'user')->latest()->paginate(20);
        return view('purchases.invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = CompanyProfile::orderBy('name')->get();
        $products = Product::orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();

        return view('purchases.invoices.create', compact('suppliers', 'products', 'branches'));
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
            'items.*.tanggal_expired' => 'nullable|date',
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

        DB::transaction(function () use ($data, $items) {
            $invoice = PurchasesInvoice::create($data + ['user_id' => auth()->id()]);
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
                    'catatan'         => "Pembelian (Invoice: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'       => 0 // Can be updated after as needed
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
        $suppliers = CompanyProfile::orderBy('name')->get();
        $products = Product::orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoice->load('items');
        return view('purchases.invoices.edit', compact('invoice', 'suppliers', 'products', 'branches'));
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
            'items.*.tanggal_expired' => 'nullable|date',
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

        DB::transaction(function () use ($invoice, $data, $items) {
            // 1. Hapus semua invoice items lama dan stok out lama terkait faktur ini
            $oldItems = $invoice->items;
            foreach ($oldItems as $old) {
                // Hapus stok 'in' pada kombinasi yang sama (opsional: tambah invoice_id di tabel stok untuk lebih akurat)
                Stock::where([
                    'product_id'      => $old->product_id,
                    'type'            => 'in',
                    'no_seri'         => $old->no_seri,
                    'tanggal_expired' => $old->tanggal_expired,
                ])->delete();
                // Setelah delete, update sisa stok per batch
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }
            $invoice->items()->delete();

            // 2. Update header
            $invoice->update($data);

            // 3. Buat item baru dan stok masuk baru
            foreach ($items as $item) {

                $invoice->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'in', // STOCK IN for purchases
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Pembelian (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });
        return redirect()->route('purchases.invoices.index')->with('success', 'Purchases Invoice berhasil diperbarui.');
    }


    public function destroy(PurchasesInvoice $invoice)
    {
        //Hapus semua item terkait
        $invoice->items()->delete();
        //Hapus faktur
        $invoice->delete();
        return redirect()->route('purchases.invoices.index')->with('success', 'Faktur Pembelian berhasil dihapus.');
    }

    protected static function generateKode()
    {
        $prefix = 'PI.' . date('ym') . '.';
        $last = PurchasesInvoice::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    public function calculateSisaStok($product_id)
    {

        $in     = Stock::where('product_id', $product_id)->where('type', 'in')->sum('jumlah');
        $out    = Stock::where('product_id', $product_id)->where('type', 'out')->sum('jumlah');
        $destroy = Stock::where('product_id', $product_id)->where('type', 'destroy')->sum('jumlah');
        return $in - $out - $destroy;
    }

    private function getSisaStokBatch($product_id, $no_seri = null, $tanggal_expired = null)
    {
        $q = Stock::where('product_id', $product_id);
        if ($no_seri) $q->where('no_seri', $no_seri);
        if ($tanggal_expired) $q->where('tanggal_expired', $tanggal_expired);

        $in     = (clone $q)->where('type', 'in')->sum('jumlah');
        $out    = (clone $q)->where('type', 'out')->sum('jumlah');
        $destroy = (clone $q)->where('type', 'destroy')->sum('jumlah');
        return $in - $out - $destroy;
    }

    /**
     * Update semua sisa_stok di tabel Stock untuk 1 batch
     */
    public static function updateAllSisaStok($product_id, $no_seri = null, $tanggal_expired = null)
    {
        $query = Stock::query()
            ->where('product_id', $product_id);
        if ($no_seri) $query->where('no_seri', $no_seri);
        if ($tanggal_expired) $query->where('tanggal_expired', $tanggal_expired);

        $stocks = $query->orderBy('id')->get();
        $runningSisa = 0;
        $subTotal = 0;
        foreach ($stocks as $stock) {
            if ($stock->type === 'in')      $runningSisa += $stock->jumlah;
            elseif ($stock->type === 'out' || $stock->type === 'destroy') $runningSisa -= $stock->jumlah;

            $stock->sisa_stok = $runningSisa;
            $stock->subtotal = $stock->jumlah * $stock->harga_net; // Update sub_total
            $stock->save();
        }
    }


    // Print
    public function print(PurchasesInvoice $invoice)
    {
        $invoice->load('items.product', 'supplier', 'user');
        return view('purchases.invoices.print', compact('invoice'));
    }
}
