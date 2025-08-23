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
        $salesGroupId = $request->sales_group_id;
        $customerId = $request->customer_id;
        $query = SalesReturn::with('customer', 'salesGroup')->orderByDesc('id');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
        }

        if ($salesGroupId) {
            $query->where('sales_group_id', $salesGroupId);
        }

        if ($customerId) {
            $query->where('company_profile_id', $customerId);
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
            ->filterColumn('customer', function ($query, $keyword) {
                $query->whereHas('customer', function ($q) use ($keyword) {
                    $q->where('name', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('sales_group', function ($query, $keyword) {
                $query->whereHas('salesGroup', function ($q) use ($keyword) {
                    $q->where('nama', 'like', "%{$keyword}%");
                });
            })
            ->filterColumn('tanggal', function ($query, $keyword) {
                $query->whereDate('tanggal', 'like', "%{$keyword}%");
            })
            ->filterColumn('grand_total', function ($query, $keyword) {
                $query->whereRaw("FORMAT(grand_total, 2) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('total_retur', function ($query, $keyword) {
                $query->whereRaw("FORMAT(total_retur, 2) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('total_bayar', function ($query, $keyword) {
                $query->whereRaw("FORMAT(total_bayar, 2) LIKE ?", ["%{$keyword}%"]);
            })
            ->filterColumn('sisa_tagihan', function ($query, $keyword) {
                $query->whereRaw("FORMAT(sisa_tagihan, 2) LIKE ?", ["%{$keyword}%"]);
            })
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }

    public function index()
    {
        return view('sales.returns.index');
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=', 'supplier') // Only customers, not suppliers
            ->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
       
        $branches = CompanyBranch::orderBy('name')->get();
        $invoices = SalesInvoice::orderBy('tanggal', 'desc')->get();

        return view('sales.returns.create', compact('customers', 'salesGroups', 'branches', 'invoices'));
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
            'items.*.tanggal_expired' => 'nullable|string',
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
                if (!isSalesInvoiceLocked($invoice->id)) {
                    $totalRetur = $retur->grand_total;
                    $invoice->total_retur += $totalRetur;
                    $invoice->sisa_tagihan = max(0, $invoice->grand_total  - $totalRetur);
                    $invoice->save();
                }

                // Update sales invoice items quantity
                foreach ($items as $item) {
                    $invItem = $invoice->items()->where('product_id', $item['product_id'])
                        // ->where('no_seri', $item['no_seri'] ?? null)
                        // ->where('tanggal_expired', $item['tanggal_expired'] ?? null)
                        ->first();

                    if ($invItem) {
                        $invItem->qty = ($invItem->qty ?? 0) - $item['qty'];
                        $invItem->save();
                    }
                }
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
        $customers = CompanyProfile::orderBy('name')
            ->where('relationship', '!=','supplier')
            ->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
        // load only products in purchases invoice
        $products = Product::whereIn('id', $return->items->pluck('product_id'))->orderBy('nama')->get();
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
            'items.*.tanggal_expired' => 'nullable|string',
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

        
        $originalKode = $return->getOriginal('kode');
        $originalGrand = $return->getOriginal('grand_total'); 

        DB::transaction(function () use ($return, $data, $items, $originalKode, $originalGrand) {
            // ✅ Update header sekali saja
            $return->update($data + ['user_id' => auth()->id()]);           

            // 1) Hapus stok 'out' lama (pakai kode LAMA & LIKE agar kebal perubahan catatan item)
            $oldItems = $return->items()->with(['product'])->get(); // ambil dulu sebelum delete
            $oldStockTimes = [];
            foreach ($oldItems as $old) {
                $stocks = Stock::where('product_id', $old->product_id)
                    ->where('type', 'in')
                    // ->when($old->no_seri, fn($q) => $q->where('no_seri', $old->no_seri))
                    // ->when($old->tanggal_expired, fn($q) => $q->where('tanggal_expired', $old->tanggal_expired))
                    ->where('catatan', 'like', "Retur Penjualan (Retur: {$originalKode})%")
                    ->get(['id','created_at']);

                foreach ($stocks as $s) {
                    $key = "{$old->product_id}";
                    // keep the earliest created_at per batch
                    $oldStockTimes[$key] = isset($oldStockTimes[$key])
                        ? min($oldStockTimes[$key], $s->created_at)
                        : $s->created_at;
                }

                 $query = Stock::where('product_id', $old->product_id)
                    ->where('type', 'in')
                    ->where(function ($q) use ($old) {
                        // filter batch bila ada
                        // $q->when($old->no_seri, fn($qq) => $qq->where('no_seri', $old->no_seri))
                        // ->when($old->tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $old->tanggal_expired));
                    })
                    ->where('catatan', 'like', "Retur Penjualan (Retur: {$originalKode})%");

                $query->delete();

                // recompute sisa stok per-batch
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }
            // 2. Hapus semua item lama
            $return->items()->delete();


            // 3. Tambah item & stok in baru
             foreach ($items as $item) {
                // ✅ Kunci baris saat hitung sisa untuk hindari race condition
                // (pakai sum ter-lock dengan cara ambil baris lalu aggregate manual)
                $sisa = self::getSisaStokBatch($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null, true);

                if ($item['qty'] > $sisa) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.*.qty" => "Stok tidak cukup untuk produk {$item['product_id']} yang dipilih. Sisa: $sisa"
                    ]);
                }

                $key = "{$item['product_id']}";
                $createdAt = $oldStockTimes[$key] ?? $return->created_at; // fallback
                $returnItem = $return->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'in',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Retur Penjualan (Retur: {$return->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                    'created_at'       => $createdAt, // pakai waktu lama untuk konsistensi
                ]);

                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }

            $makeKey = function ($pid) {
                // If you want batch-level accuracy, include seri/exp in the key.
                // return "{$pid}||{$seri}||{$exp}";
                return (string)$pid; // only by product_id
            };

            $oldAgg = [];
            foreach ($oldItems as $it) {
                $k = $makeKey($it->product_id);
                $oldAgg[$k] = ($oldAgg[$k] ?? 0) + (float)$it->qty;
            }

            $newAgg = [];
            foreach ($items as $it) {
                $k = $makeKey($it['product_id']);
                $newAgg[$k] = ($newAgg[$k] ?? 0) + (float)$it['qty'];
            }
            
            // 4. update sales invoice total_return and sisa_tagihan
            if ($return->sales_invoice_id) {
                $invoice = SalesInvoice::lockForUpdate()->find($return->sales_invoice_id); // 👈 avoid races
                if ($invoice && !isSalesInvoiceLocked($invoice->id)) {

                    $delta = ($return->grand_total ?? 0) - ($originalGrand ?? 0); // 👈 ONLY the change

                    if ($delta != 0) {
                        $invoice->total_retur = max(0, ($invoice->total_retur ?? 0) + $delta);
                    }

                    // Recompute sisa_tagihan correctly (depends on your model fields)
                    // If you track payments separately as total_bayar:
                    $paid = $invoice->total_bayar ?? 0;
                    $invoiceTotal = $invoice->grand_total ?? 0;
                    $returTotal = $invoice->total_retur ?? 0;

                    $invoice->sisa_tagihan = max(0, $invoiceTotal - $paid - $returTotal);

                    $invoice->save();

                    $allKeys = array_unique(array_merge(array_keys($oldAgg), array_keys($newAgg)));
                    foreach ($allKeys as $k) {
                        $oldQty = (float)($oldAgg[$k] ?? 0);
                        $newQty = (float)($newAgg[$k] ?? 0);
                        $deltaQty = $newQty - $oldQty; // positive = more returned now

                        if ($deltaQty == 0.0) continue;

                        // resolve key back to product_id (and optionally seri/expired)
                        // If you used the batch key, parse it here.
                        $productId = (int)$k;

                        $invItemQ = $invoice->items()->where('product_id', $productId);

                        // If you want batch level matching, uncomment:
                        // [$pid, $seri, $exp] = explode('||', $k);
                        // $invItemQ->where(function ($q) use ($seri, $exp) {
                        //     $q->where('no_seri', $seri)->where('tanggal_expired', $exp);
                        // });

                        $invItem = $invItemQ->lockForUpdate()->first();
                        if ($invItem) {
                            // Returned qty reduces sold qty; delta positive => subtract more
                            $invItem->qty = max(0, (float)($invItem->qty ?? 0) - $deltaQty);
                            $invItem->save();
                        }
                        // else: if invoice might not have a matching item, decide whether to create one or skip.
                    }
                }
            }
        });

        

        return redirect()->route('sales.returns.index')->with('success', 'Retur penjualan berhasil diupdate.');
    }

    public function destroy(SalesReturn $return)
    {
        // Hapus semua item dan stok masuk terkait
        $oldItems = $return->items;
        $originalKode = $return->getOriginal('kode');
        foreach ($oldItems as $old) {
            Stock::where([
                'product_id'      => $old->product_id,
                'type'            => 'in',
            ])
            ->where('catatan', 'like', "Retur Penjualan (Retur: {$originalKode})%")
            ->delete();
            self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
        }

        // Hapus retur dan relasinya
        // Update sales invoice jika ada
        if ($return->sales_invoice_id) {
            $invoice = SalesInvoice::find($return->sales_invoice_id);
            if ($invoice) {
                if (!isSalesInvoiceLocked($invoice->id)) {
                    $invoice->total_retur -= $return->grand_total;
                    $invoice->sisa_tagihan = max(0, $invoice->sisa_tagihan + $return->grand_total);
                    $invoice->save();
                }

                // Update sales invoice items quantity
                foreach ($return->items as $item) {
                    $invItem = $invoice->items()->where('product_id', $item->product_id)
                        // ->where('no_seri', $item->no_seri ?? null)
                        // ->where('tanggal_expired', $item->tanggal_expired ?? null)
                        ->first();  
                    if ($invItem) {
                        $invItem->qty = ($invItem->qty ?? 0) + $item->qty;
                        $invItem->save();
                    }
                }
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
     private function getSisaStokBatch($product_id, $no_seri = null, $tanggal_expired = null, $lock = false)
    {
        $q = Stock::where('product_id', $product_id);
        // ->when($no_seri, fn($qq) => $qq->where('no_seri', $no_seri))
        // ->when($tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $tanggal_expired));

        if ($lock) $q->lockForUpdate();

        $rows = $q->get(); // biar konsisten saat lock
        $in = $rows->where('type','in')->sum('jumlah');
        $out = $rows->where('type','out')->sum('jumlah');
        $destroy = $rows->where('type','destroy')->sum('jumlah');
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
            elseif (in_array($stock->type, ['out','destroy'])) $runningSisa -= $stock->jumlah;

            $stock->sisa_stok = $runningSisa;
            $stock->subtotal = $stock->jumlah * $stock->harga_net;
            $stock->save();
        }
    }

    // In SalesReturnController
    public function getInvoiceProductsOptions(Request $request, $invoiceId)
    {
        $q = $request->get('q', '');

        $invoice = SalesInvoice::with('items.product')->findOrFail($invoiceId);

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
                'kode' => $p->kode,
                'nama' => $p->nama,
                'satuan' => $p->satuan_kecil,
                'sisa_stok' => self::getSisaStokBatch($p->id),
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
                    'sisa_stok' => self::getSisaStokBatch($item->product_id, $item->no_seri, $item->tanggal_expired),
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

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        $base = SalesReturn::query();
        if ($awal && $akhir) {
            $base->whereBetween('tanggal', [$awal, $akhir]);
        }

        // Distinct keys from invoices in range
        $customerIds   = (clone $base)->whereNotNull('company_profile_id')->distinct()->pluck('company_profile_id');
        $salesGroupIds = (clone $base)->whereNotNull('sales_group_id')->distinct()->pluck('sales_group_id');

        // Fetch display names
        $customers   = CompanyProfile::whereIn('id', $customerIds)->orderBy('name')->get(['id', 'name']);
        $salesGroups = SalesGroup::whereIn('id', $salesGroupIds)->orderBy('nama')->get(['id', 'nama']);


        return response()->json([
            'customers'    => $customers,
            'sales_groups' => $salesGroups,
        ]);
    }
}
