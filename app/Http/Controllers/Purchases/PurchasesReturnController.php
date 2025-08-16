<?php

namespace App\Http\Controllers\Purchases;

use App\Models\PurchasesReturn;
use App\Models\PurchasesReturnItem;
use App\Models\CompanyProfile;
use App\Models\Product;
use App\Models\CompanyBranch;
use App\Models\PurchasesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;
use App\Models\Stock;

class PurchasesReturnController extends Controller
{
    public function datatable(Request $request)
    {
        $awal  = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $supplierId = $request->supplier_id;
        $query = PurchasesReturn::with('supplier')->orderByDesc('id');

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
            ->addColumn('aksi', function ($r) {
                return view('purchases.returns.partials.aksi', ['row' => $r])->render();
            })

            ->filterColumn('supplier', function ($query, $keyword) {
                $query->whereHas('supplier', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(tanggal, '%d %b %Y') LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('grand_total', function ($query, $keyword) {
                $query->whereRaw("FORMAT(grand_total, 2) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('total_retur', function ($query, $keyword) {
                $query->whereRaw("CAST(total_retur AS CHAR) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('total_bayar', function ($query, $keyword) {
                $query->whereRaw("CAST(total_bayar AS CHAR) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('sisa_tagihan', function ($query, $keyword) {
                $query->whereRaw("CAST(sisa_tagihan AS CHAR) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('created_at', function ($query, $keyword) {
                $query->whereRaw("DATE_FORMAT(created_at, '%d %b %Y %H:%i') LIKE ?", ["%{$keyword}%"]);
            })
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }

    public function index()
    {
        return view('purchases.returns.index');
    }

    public function create()
    {
        $suppliers = CompanyProfile::orderBy('name')
            ->where('relationship', 'supplier')
            ->get();
      
        $branches = CompanyBranch::orderBy('name')->get();
        $invoices = PurchasesInvoice::orderBy('tanggal', 'desc')->get();
        return view('purchases.returns.create', compact('suppliers', 'branches', 'invoices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:purchases_returns,kode',
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer',
            'purchases_invoice_id' => 'nullable|integer',
            'tipe_retur' => 'required|string|max:50',
            'catatan' => 'nullable|string',
            'diskon_faktur' => 'nullable|numeric',
            'diskon_ppn' => 'nullable|numeric',
            'subtotal' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total_retur' => 'nullable|numeric',
            'total_bayar' => 'nullable|numeric',
            'sisa_tagihan' => 'nullable|numeric',
            // Items array
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.no_seri' => 'nullable|string|max:50',
            'items.*.tanggal_expired' => 'nullable|date',
            'items.*.satuan' => 'nullable|string|max:20',
            'items.*.harga_satuan' => 'required|numeric',
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

        if (!$data['kode'] || $data['kode'] == '(auto)') {
            $data['kode'] = self::generateKode();
        }

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($data, $items, &$retur) {
            $retur = PurchasesReturn::create($data + ['user_id' => auth()->id()]);
            foreach ($items as $item) {
                // 1. Insert return item
                $retur->items()->create($item);

                // 2. Tambahkan stok keluar (karena retur = barang kembali)
                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Retur Pembelian (Retur: {$retur->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0, // Akan diupdate setelah ini
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });


        // Update purchases invoice total_return and sisa_tagihan jika ada
        if ($data['purchases_invoice_id']) {
            $invoice = PurchasesInvoice::find($data['purchases_invoice_id']);
            if ($invoice) {
                $totalRetur = $retur->grand_total;
                $invoice->total_retur = $totalRetur;
                $invoice->sisa_tagihan = max(0, $invoice->grand_total - $totalRetur);
                $invoice->save();
            }
        }

        return redirect()->route('purchases.returns.index')->with('success', 'Retur pembelian berhasil dibuat.');
    }

    public function show(PurchasesReturn $return)
    {
        $return->load('items.product', 'supplier', 'user', 'purchasesInvoice');
        return view('purchases.returns.show', compact('return'));
    }

    public function edit(PurchasesReturn $return)
    {
        $suppliers = CompanyProfile::orderBy('name')
            ->where('relationship', 'supplier')
            ->get();
        $products = Product::whereIn('id', $return->items->pluck('product_id'))->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoices = PurchasesInvoice::orderBy('tanggal', 'desc')->get();
        $return->load('items');
        return view('purchases.returns.edit', compact('return', 'suppliers', 'products', 'branches', 'invoices'));
    }

    public function update(Request $request, PurchasesReturn $return)
    {
        try {
            //code...
            $data = $request->validate([
                'tanggal' => 'required|date',
                'company_profile_id' => 'required|integer',
                'purchases_invoice_id' => 'nullable|integer',
                'tipe_retur' => 'required|string|max:50',
                'catatan' => 'nullable|string',
                'diskon_faktur' => 'nullable|numeric',
                'diskon_ppn' => 'nullable|numeric',
                'subtotal' => 'nullable|numeric',
                'grand_total' => 'nullable|numeric',
                'total_retur' => 'nullable|numeric',
                'total_bayar' => 'nullable|numeric',
                'sisa_tagihan' => 'nullable|numeric',
                // Items array
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|integer|exists:products,id',
                'items.*.qty' => 'required|numeric|min:1',
                'items.*.no_seri' => 'nullable|string|max:50',
                'items.*.tanggal_expired' => 'nullable|date',
                'items.*.satuan' => 'nullable|string|max:20',
                'items.*.harga_satuan' => 'required|numeric',
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
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($return, $data, $items) {
            $return->update($data + ['user_id' => auth()->id()]);

            // 1. Hapus item lama dan stok 'in' lama terkait retur ini
            $oldItems = $return->items;
            foreach ($oldItems as $old) {
                Stock::where([
                    'product_id'      => $old->product_id,
                    'type'            => 'out',
                    'no_seri'         => $old->no_seri,
                    'tanggal_expired' => $old->tanggal_expired,
                    'harga_net'       => $old->harga_satuan,
                ])->delete();
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }
            $return->items()->delete();

            // 2. Update header
            $return->update($data);

            // 3. Tambah item & stok out baru
            foreach ($items as $item) {
                $return->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Retur Pembelian (Update: {$return->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        // 4. update purchase invoice total_return and sisa_tagihan
        if ($return->purchases_invoice_id) {
            $invoice = PurchasesInvoice::find($return->purchases_invoice_id);
            if ($invoice) {
                // If invoice is locked, we cannot update it
                $totalRetur = $return->grand_total;
                $invoice->total_retur = $totalRetur;
                $invoice->sisa_tagihan = max(0, $invoice->grand_total - $totalRetur);
                $invoice->save();
            }
        }

        return redirect()->route('purchases.returns.index')->with('success', 'Retur pembelian berhasil diupdate.');
    }

    public function destroy(PurchasesReturn $return)
    {
        // Hapus semua item dan stok masuk terkait
        $oldItems = $return->items;
        foreach ($oldItems as $old) {
            Stock::where([
                'product_id'      => $old->product_id,
                'type'            => 'out',
                'no_seri'         => $old->no_seri,
                'tanggal_expired' => $old->tanggal_expired,
                'harga_net'       => $old->harga_satuan,
            ])->delete();
            self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
        }

        // Update purchase invoice jika ada
        if ($return->purchases_invoice_id) {
            $invoice = PurchasesInvoice::find($return->purchases_invoice_id);
            if ($invoice) {
                $invoice->total_retur -= $return->grand_total;
                $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $return->grand_total);
                $invoice->save();
            }
        }

        $return->items()->delete();
        $return->delete();
        return redirect()->route('purchases.returns.index')->with('success', 'Retur pembelian berhasil dihapus.');
    }

    /** Kode retur auto: RT.2507.00001 */
    protected static function generateKode()
    {
        $prefix = 'RT.' . date('ym') . '.';
        $last = PurchasesReturn::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    /** Sisa stok per batch */
    private function getSisaStokBatch($product_id, $no_seri = null, $tanggal_expired = null)
    {
        $q = Stock::where('product_id', $product_id);
        // if ($no_seri) $q->where('no_seri', $no_seri);
        // if ($tanggal_expired) $q->where('tanggal_expired', $tanggal_expired);

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
        foreach ($stocks as $stock) {
            if ($stock->type === 'in')      $runningSisa += $stock->jumlah;
            elseif ($stock->type === 'out' || $stock->type === 'destroy') $runningSisa -= $stock->jumlah;

            $stock->sisa_stok = $runningSisa;
            $stock->subtotal = $stock->jumlah * $stock->harga_net; // Update sub_total
            $stock->save();
        }
    }

    // In PurchasesReturnController
    public function getInvoiceProductsOptions(Request $request, $invoiceId)
    {
        $q = $request->get('q', '');

        $invoice = PurchasesInvoice::with('items.product')->findOrFail($invoiceId);

        // Get all unique products from invoice items
        $allProducts = $invoice->items->map(fn($item) => $item->product)->unique('id');

        // If there is a search, filter; otherwise, take first 10
        $filteredProducts = $q
            ? $allProducts->filter(function ($p) use ($q) {
                return str_contains(strtolower($p->kode), strtolower($q))
                    || str_contains(strtolower($p->nama), strtolower($q));
            })
            : $allProducts->sortBy('kode')->take(10);

        return response()->json([
            'products' => $filteredProducts->map(fn($p) => [
                'id' => $p->id,
                'text' => $p->kode . ' - ' . $p->nama,
                'satuan' => $p->satuan_kecil,
            ])->values(),
        ]);
    }

    public function getReturnProductBatchOptions($purchaseInvoiceId, $productId)
    {
        $invoice = PurchasesInvoice::with('items')->findOrFail($purchaseInvoiceId);
        $items = $invoice->items->where('product_id', $productId)->values();

        return response()->json([
            'batches' => $items->map(function ($item) {
                return [
                    'no_seri' => $item->no_seri,
                    'tanggal_expired' => $item->tanggal_expired,
                    'harga_satuan' => $item->harga_satuan,
                    'satuan' => $item->satuan,
                    'qty' => $item->qty,
                    'diskon_1_persen' => $item->diskon_1_persen,
                    'diskon_1_rupiah' => $item->diskon_1_rupiah,
                    'diskon_2_persen' => $item->diskon_2_persen,
                    'diskon_2_rupiah' => $item->diskon_2_rupiah,
                    'diskon_3_persen' => $item->diskon_3_persen,
                    'diskon_3_rupiah' => $item->diskon_3_rupiah,
                    'sub_total_sblm_disc' => $item->sub_total_sblm_disc,
                    'total_diskon_item' => $item->total_diskon_item,
                    'sub_total_sebelum_ppn' => $item->sub_total_sebelum_ppn,
                    'ppn_persen' => $item->ppn_persen,
                    'sub_total_setelah_disc' => $item->sub_total_setelah_disc,
                    'catatan' => $item->catatan,
                ];
            }),
        ]);
    }

    // Controller
    public function filterInvoices(Request $request)
    {
        $q = PurchasesInvoice::with('supplier');
        if ($request->supplier_id) {
            $q->where('company_profile_id', $request->supplier_id);
        }

        $invoices = $q->orderBy('tanggal', 'desc')->get();

        return response()->json([
            'invoices' => $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'kode' => $inv->kode,
                    'supplier_id' => $inv->company_profile_id,
                    'supplier_name' => $inv->supplier->name ?? '-',
                ];
            })
        ]);
    }
    public function print($id)
    {
        $invoice = PurchasesReturn::with(['supplier', 'items.product'])->findOrFail($id);
        return view('purchases.returns.print', compact('invoice'));
    }

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        $base = PurchasesReturn::query();
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
