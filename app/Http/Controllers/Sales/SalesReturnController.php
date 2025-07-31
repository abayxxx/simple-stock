<?php

namespace App\Http\Controllers\Sales;

use App\Models\SalesReturn;
use App\Models\SalesReturnItem;
use App\Models\CompanyProfile;
use App\Models\SalesGroup;
use App\Models\Product;
use App\Models\CompanyBranch;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;
use App\Models\Stock;

class SalesReturnController extends Controller
{
    public function datatable(Request $request)
    {
        $awal  = $request->periode_awal;
        $akhir = $request->periode_akhir;
        $query = SalesReturn::with('customer', 'salesGroup');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal, $akhir]);
        }

        return DataTables::of($query)
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->addColumn('sales_group', fn($r) => $r->salesGroup->nama ?? '-')
            ->editColumn('grand_total', fn($r) => number_format($r->grand_total, 2, ',', '.'))
            ->editColumn('total_retur', fn($r) => number_format($r->total_retur, 2, ',', '.'))
            ->editColumn('total_bayar', fn($r) => number_format($r->total_bayar, 2, ',', '.'))
            ->editColumn('sisa_tagihan', fn($r) => number_format($r->sisa_tagihan, 2, ',', '.'))
            ->editColumn('created_at', fn($r) => $r->created_at->format('d M Y H:i'))
            ->addColumn('aksi', function ($r) {
                return view('sales.returns.partials.aksi', ['row' => $r])->render();
            })
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }

    public function index()
    {
        $returns = SalesReturn::with('customer', 'salesGroup', 'user')->latest()->paginate(20);
        return view('sales.returns.index', compact('returns'));
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
        $products = Product::with('stocks')->whereHas('stocks', function ($q) {
            $q->where('type', 'in');
        })->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoices = SalesInvoice::orderBy('tanggal', 'desc')->get();

        return view('sales.returns.create', compact('customers', 'salesGroups', 'products', 'branches', 'invoices'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sales_returns,kode',
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer',
            'sales_invoice_id' => 'nullable|integer',
            'sales_group_id' => 'nullable|integer',
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
            $retur = SalesReturn::create($data + ['user_id' => auth()->id()]);
            foreach ($items as $item) {
                // 1. Insert return item
                $retur->items()->create($item);

                // 2. Tambahkan stok masuk (karena retur = barang kembali)
                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'in',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Retur Penjualan (Retur: {$retur->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0, // Akan diupdate setelah ini
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });


        // Update sales invoice total_return and sisa_tagihan jika ada
        if ($data['sales_invoice_id']) {
            $invoice = SalesInvoice::find($data['sales_invoice_id']);
            if ($invoice) {
                $totalRetur = $retur->grand_total;
                $invoice->total_retur += $totalRetur;
                $invoice->sisa_tagihan = max(0, $invoice->grand_total  - $totalRetur);
                $invoice->save();
            }
        }

        return redirect()->route('sales.returns.index')->with('success', 'Retur penjualan berhasil dibuat.');
    }

    public function show(SalesReturn $return)
    {
        $return->load('items.product', 'customer', 'salesGroup', 'user', 'salesInvoice');
        return view('sales.returns.show', compact('return'));
    }

    public function edit(SalesReturn $return)
    {
        $customers = CompanyProfile::orderBy('name')->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
        $products = Product::with('stocks')->whereHas('stocks', function ($q) {
            $q->where('type', 'in');
        })->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoices = SalesInvoice::orderBy('tanggal', 'desc')->get();
        $return->load('items');
        return view('sales.returns.edit', compact('return', 'customers', 'salesGroups', 'products', 'branches', 'invoices'));
    }

    public function update(Request $request, SalesReturn $return)
    {
        $data = $request->validate([
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer',
            'sales_invoice_id' => 'nullable|integer',
            'sales_group_id' => 'nullable|integer',
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
        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($return, $data, $items) {
            // 1. Hapus item lama dan stok 'in' lama terkait retur ini
            $oldItems = $return->items;
            foreach ($oldItems as $old) {
                Stock::where([
                    'product_id'      => $old->product_id,
                    'type'            => 'in',
                    'no_seri'         => $old->no_seri,
                    'tanggal_expired' => $old->tanggal_expired,
                    'harga_net'       => $old->harga_satuan,
                ])->delete();
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }
            $return->items()->delete();

            // 2. Update header
            $return->update($data);

            // 3. Tambah item & stok in baru
            foreach ($items as $item) {
                $return->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'in',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Retur Penjualan (Update: {$return->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        // 4. update sales invoice total_return and sisa_tagihan
        if ($return->sales_invoice_id) {
            $invoice = SalesInvoice::find($return->sales_invoice_id);
            if ($invoice) {
                $totalRetur = $return->grand_total;
                $invoice->total_retur = $totalRetur;
                $invoice->sisa_tagihan = max(0, $invoice->grand_total - $totalRetur);
                $invoice->save();
            }
        }

        return redirect()->route('sales.returns.index')->with('success', 'Retur penjualan berhasil diupdate.');
    }

    public function destroy(SalesReturn $return)
    {
        // Hapus semua item dan stok masuk terkait
        $oldItems = $return->items;
        foreach ($oldItems as $old) {
            Stock::where([
                'product_id'      => $old->product_id,
                'type'            => 'in',
                'no_seri'         => $old->no_seri,
                'tanggal_expired' => $old->tanggal_expired,
                'harga_net'       => $old->harga_satuan,
            ])->delete();
            self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
        }

        // Hapus retur dan relasinya
        // Update sales invoice jika ada
        if ($return->sales_invoice_id) {
            $invoice = SalesInvoice::find($return->sales_invoice_id);
            if ($invoice) {
                $invoice->total_retur -= $return->grand_total;
                $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $return->grand_total);
                $invoice->save();
            }
        }

        $return->items()->delete();
        $return->delete();
        return redirect()->route('sales.returns.index')->with('success', 'Retur penjualan berhasil dihapus.');
    }

    /** Kode retur auto: RT.2507.00001 */
    protected static function generateKode()
    {
        $prefix = 'RT.' . date('ym') . '.';
        $last = SalesReturn::where('kode', 'like', $prefix . '%')->max('kode');
        $urut = $last ? (int)substr($last, 8) + 1 : 1;
        return $prefix . str_pad($urut, 5, '0', STR_PAD_LEFT);
    }

    /** Sisa stok per batch */
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
        foreach ($stocks as $stock) {
            if ($stock->type === 'in')      $runningSisa += $stock->jumlah;
            elseif ($stock->type === 'out' || $stock->type === 'destroy') $runningSisa -= $stock->jumlah;

            $stock->sisa_stok = $runningSisa;
            $stock->subtotal = $stock->jumlah * $stock->harga_net; // Update sub_total
            $stock->save();
        }
    }

    // In SalesReturnController
    public function getInvoiceProductsOptions($invoiceId)
    {
        $invoice = \App\Models\SalesInvoice::with('items.product')->findOrFail($invoiceId);

        // Get unique products from invoice items
        $products = $invoice->items->map(fn($item) => $item->product)->unique('id');

        // Return as JSON (for building <option> in JS)
        return response()->json([
            'products' => $products->map(fn($p) => [
                'id' => $p->id,
                'text' => $p->kode . ' - ' . $p->nama,
            ])->values(),
        ]);
    }

    public function getReturnProductBatchOptions($salesInvoiceId, $productId)
    {
        $invoice = SalesInvoice::with('items')->findOrFail($salesInvoiceId);
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
        $q = \App\Models\SalesInvoice::with('customer');
        if ($request->customer_id) {
            $q->where('company_profile_id', $request->customer_id);
        }
        if ($request->sales_group_id) {
            $q->where('sales_group_id', $request->sales_group_id);
        }
        $invoices = $q->orderBy('tanggal', 'desc')->get();

        return response()->json([
            'invoices' => $invoices->map(function ($inv) {
                return [
                    'id' => $inv->id,
                    'kode' => $inv->kode,
                    'customer_id' => $inv->company_profile_id,
                    'customer_name' => $inv->customer->name ?? '-',
                    'sales_group_id' => $inv->sales_group_id,
                ];
            })
        ]);
    }
    public function print($id)
    {
        $invoice = SalesReturn::with(['customer', 'items.product', 'salesGroup'])->findOrFail($id);
        return view('sales.returns.print', compact('invoice'));
    }
}
