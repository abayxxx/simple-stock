<?php

// app/Http/Controllers/SalesInvoiceController.php

namespace App\Http\Controllers\Sales;

use App\Exports\SalesInvoicesExport;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\CompanyProfile;
use App\Models\SalesGroup;
use App\Models\Product;
use App\Models\CompanyBranch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Controllers\Controller;
use App\Models\Stock;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Validation\ValidationException;
use Maatwebsite\Excel\Facades\Excel;

class SalesInvoiceController extends Controller
{

    public function datatable(Request $request)
    {
        $awal          = $request->periode_awal;
        $akhir         = $request->periode_akhir;
        $customerId    = $request->customer_id;     // from #filter_customer
        $lokasiId      = $request->lokasi_id;       // from #filter_lokasi
        $salesGroupId  = $request->sales_group_id;  // from #filter_sg

        $query = SalesInvoice::with(['customer', 'location', 'salesGroup'])->orderByDesc('id');

        if ($awal && $akhir) {
            $query->whereBetween('tanggal', [$awal . ' 00:00:00', $akhir . ' 23:59:59']);
        }
        if ($customerId) {
            $query->where('company_profile_id', $customerId);
        }
        if ($lokasiId) {
            $query->where('lokasi_id', $lokasiId);
        }
        if ($salesGroupId) {
            $query->where('sales_group_id', $salesGroupId);
        }

        return DataTables::of($query)
            ->editColumn('tanggal', fn($r) => tanggal_indo($r->tanggal))
            ->addColumn('customer', fn($r) => $r->customer->name ?? '-')
            ->addColumn('lokasi', fn($r) => $r->lokasi_id ? $r->location->name : '-')
            ->addColumn('sales_group', fn($r) => $r->salesGroup->nama ?? '-')
            ->editColumn('grand_total', fn($r) => number_format($r->grand_total, 2, ',', '.'))
            ->editColumn('total_retur', fn($r) => number_format($r->total_retur, 2, ',', '.'))
            ->editColumn('total_bayar', fn($r) => number_format($r->total_bayar, 2, ',', '.'))
            ->editColumn('sisa_tagihan', fn($r) => number_format($r->sisa_tagihan, 2, ',', '.'))
            ->editColumn('created_at', fn($r) => $r->created_at->format('d M Y H:i'))
            ->addColumn('tgl_pembayaran', fn($r) => $r->paymentItems->count() > 0 ? $r->latestPayment()->first()->created_at->format('d M Y H:i') : '-')

            // 🔎 Make related columns searchable
            ->filterColumn('customer', function ($q, $keyword) {
                $q->whereHas('customer', fn($qq) => $qq->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('lokasi', function ($q, $keyword) {
                $q->whereHas('location', fn($qq) => $qq->where('name', 'like', "%{$keyword}%"));
            })
            ->filterColumn('sales_group', function ($q, $keyword) {
                $q->whereHas('salesGroup', fn($qq) => $qq->where('nama', 'like', "%{$keyword}%"));
            })

            // 🔎 Make formatted date searchable (search by raw date or formatted string)
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

            ->addColumn('aksi', fn($r) => view('sales.invoices.partials.aksi', ['row' => $r])->render())
            ->rawColumns(['grand_total', 'total_retur', 'total_bayar', 'sisa_tagihan', 'aksi'])
            ->make(true);
    }


    public function index()
    {
        return view('sales.invoices.index');
    }

    public function create()
    {
        $customers = CompanyProfile::orderBy('name')
        ->where('relationship', '!=', 'supplier') // Ensure not a supplier
        ->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
       
        $branches = CompanyBranch::orderBy('name')->get();

        return view('sales.invoices.create', compact('customers', 'salesGroups', 'branches'));
    }

   public function store(Request $request)
    {
        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sales_invoices,kode',
            'auto_kode' => 'nullable|in:1',
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer',
            'sales_group_id' => 'nullable|integer',
            'lokasi_id' => 'nullable|integer',
            'term' => 'nullable|string|max:50',
            'is_tunai' => 'boolean',
            'no_po' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
            'diskon_faktur' => 'nullable|numeric',
            'diskon_ppn' => 'nullable|numeric',
            'jatuh_tempo' => 'nullable|date',

            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
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

        $data['diskon_faktur'] = $data['diskon_faktur'] ?? 0;
        $data['diskon_ppn']    = $data['diskon_ppn'] ?? 0;
        $data['is_tunai']      = (bool)($data['is_tunai'] ?? false);

        if ($request->boolean('auto_kode')) {
            $data['kode'] = self::generateKode();
        } else {
            if (empty($data['kode']) || $data['kode'] === '(auto)') {
                return back()->withInput()->withErrors(['kode' => 'Nomor Faktur harus diisi jika mode auto tidak dipilih']);
            }
        }

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use (&$invoice, $data, $items) {
            $subtotal = 0;
            foreach ($items as $it) {
                $line = (float)$it['qty'] * (float)$it['harga_satuan'];
                $p1 = $it['diskon_1_persen'] ?? 0; if ($p1) $line -= $line * ($p1/100);
                $p2 = $it['diskon_2_persen'] ?? 0; if ($p2) $line -= $line * ($p2/100);
                $p3 = $it['diskon_3_persen'] ?? 0; if ($p3) $line -= $line * ($p3/100);
                $r1 = $it['diskon_1_rupiah'] ?? 0; $line -= $r1;
                $r2 = $it['diskon_2_rupiah'] ?? 0; $line -= $r2;
                $r3 = $it['diskon_3_rupiah'] ?? 0; $line -= $r3;
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

            $invoice = SalesInvoice::create($header);

            foreach ($items as $item) {
                $batchRows = DB::table('stocks')
                    ->where('product_id', $item['product_id'])
                    // ->when(!empty($item['no_seri']), fn($q) => $q->where('no_seri', $item['no_seri']))
                    // ->when(!empty($item['tanggal_expired']), fn($q) => $q->where('tanggal_expired', $item['tanggal_expired']))
                    ->lockForUpdate()
                    ->get();

                $available = ($batchRows->where('type','in')->sum('jumlah'))
                            - ($batchRows->where('type','out')->sum('jumlah'))
                            - ($batchRows->where('type','destroy')->sum('jumlah'));

                if ($item['qty'] > $available) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.*.qty" => "Stok tidak cukup untuk produk {$item['product_id']} di batch yang dipilih. Sisa: {$available}"
                    ]);
                }

                $invoiceItem = $invoice->items()->create($item);

                $stockId = Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Penjualan (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                ])->id;

                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        return redirect()->route('sales.invoices.index')->with('success', 'Faktur penjualan berhasil dibuat.');
    }


    public function show(SalesInvoice $invoice)
    {
        $invoice->load('items.product', 'customer', 'salesGroup', 'user');

        return view('sales.invoices.show', compact('invoice'));
    }

    public function edit(SalesInvoice $invoice)
    {

        //check if invoice is locked
        if ($invoice->is_locked) {
            return redirect()->route('sales.invoices.index')->withErrors(['error' => 'Faktur ini sudah terkunci dan tidak bisa diubah.']);
        }

        $customers = CompanyProfile::orderBy('name')
        ->where('relationship', '!=', 'supplier') // Ensure not a supplier
            ->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
        // load only products in purchases invoice
        $products = Product::whereIn('id', $invoice->items->pluck('product_id'))->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoice->load('items');
        return view('sales.invoices.edit', compact('invoice', 'customers', 'salesGroups', 'products', 'branches'));
    }

   public function update(Request $request, SalesInvoice $invoice)
    {
        // ✅ Block jika locked
        // if ($invoice->is_locked) {
        //     return redirect()->route('sales.invoices.index')
        //         ->withErrors(['error' => 'Faktur ini sudah terkunci dan tidak bisa diubah.']);
        // }

        $data = $request->validate([
            'kode' => 'nullable|string|max:50|unique:sales_invoices,kode,' . $invoice->id,
            'auto_kode' => 'nullable|in:1',
            'tanggal' => 'required|date',
            'company_profile_id' => 'required|integer',
            'sales_group_id' => 'nullable|integer',
            'lokasi_id' => 'nullable|integer',
            'term' => 'nullable|string|max:50',
            'is_tunai' => 'boolean',
            'no_po' => 'nullable|string|max:100',
            'catatan' => 'nullable|string',
            'diskon_faktur' => 'nullable|numeric',
            'diskon_ppn' => 'nullable|numeric',
            'subtotal' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total_retur' => 'nullable|numeric',
            'total_bayar' => 'nullable|numeric',
            'sisa_tagihan' => 'nullable|numeric',
            'jatuh_tempo' => 'nullable|date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
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

        $data['diskon_faktur'] = $data['diskon_faktur'] ?? 0;
        $data['diskon_ppn']    = $data['diskon_ppn'] ?? 0;

        $items = $data['items'];
        unset($data['items']);

        // simpan kode lama untuk menghapus stok lama dengan tepat
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

            // 1) Hapus stok 'out' lama (pakai kode LAMA & LIKE agar kebal perubahan catatan item)
            $oldItems = $invoice->items()->with(['product'])->get(); // ambil dulu sebelum delete
            $oldStockTimes = [];
            foreach ($oldItems as $old) {
                $stocks = Stock::where('product_id', $old->product_id)
                    ->where('type', 'out')
                    // ->when($old->no_seri, fn($q) => $q->where('no_seri', $old->no_seri))
                    // ->when($old->tanggal_expired, fn($q) => $q->where('tanggal_expired', $old->tanggal_expired))
                    ->where('catatan', 'like', "Penjualan (Faktur: {$originalKode})%")
                    ->get(['id','created_at']);

                foreach ($stocks as $s) {
                    $key = "{$old->product_id}";
                    // keep the earliest created_at per batch
                    $oldStockTimes[$key] = isset($oldStockTimes[$key])
                        ? min($oldStockTimes[$key], $s->created_at)
                        : $s->created_at;
                }

                 $query = Stock::where('product_id', $old->product_id)
                    ->where('type', 'out')
                    ->where(function ($q) use ($old) {
                        // filter batch bila ada
                        // $q->when($old->no_seri, fn($qq) => $qq->where('no_seri', $old->no_seri))
                        // ->when($old->tanggal_expired, fn($qq) => $qq->where('tanggal_expired', $old->tanggal_expired));
                    })
                    ->where('catatan', 'like', "Penjualan (Faktur: {$originalKode})%");

                $query->delete();

                // recompute sisa stok per-batch
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }

            // 2) Hapus item lama
            $invoice->items()->delete();

            // 3) Tambah item baru + stok 'out' baru
            foreach ($items as $item) {
                // ✅ Kunci baris saat hitung sisa untuk hindari race condition
                // (pakai sum ter-lock dengan cara ambil baris lalu aggregate manual)
                $sisa = self::getSisaStokBatch($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null, true);

                if ($item['qty'] > $sisa) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.*.qty" => "Stok tidak cukup untuk produk {$item['product_id']} di seri/expired yang dipilih. Sisa: $sisa"
                    ]);
                }

                $key = "{$item['product_id']}";
                $createdAt = $oldStockTimes[$key] ?? $invoice->created_at; // fallback
                $invoiceItem = $invoice->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Penjualan (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                    'created_at'       => $createdAt, // pakai waktu lama untuk konsistensi
                ]);

                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        return redirect()->route('sales.invoices.index')->with('success', 'Faktur penjualan berhasil diupdate.');
    }


    public function destroy(SalesInvoice $invoice)
    {
        // Hapus stok 'out' terkait
       $items = $invoice->items()->with(['product'])->get();
        foreach ($items as $item) {
            $stocks = Stock::where('product_id', $item->product_id)
                ->where('type', 'out')
                // ->when($item->no_seri, fn($q) => $q->where('no_seri', $item->no_seri))
                // ->when($item->tanggal_expired, fn($q) => $q->where('tanggal_expired', $item->tanggal_expired))
                ->where('catatan', 'like', "Penjualan (Faktur: {$invoice->kode})%")
                ->delete(); 
            // recompute sisa stok per-batch
            self::updateAllSisaStok($item->product_id, $item->no_seri, $item->tanggal_expired);
        }
       
        //Hapus semua item terkait
        $invoice->items()->delete();
        //Hapus faktur
        $invoice->delete();
        return redirect()->route('sales.invoices.index')->with('success', 'Faktur penjualan berhasil dihapus.');
    }

    protected static function generateKode()
    {
        $prefix = 'HR.' . date('dm') . '.';
        $last = SalesInvoice::where('kode', 'like', $prefix . '%')->max('kode');
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


    public function print($id)
    {
        $invoice = SalesInvoice::with(['customer', 'items.product', 'salesGroup'])->findOrFail($id);
        
        return view('sales.invoices.print', compact('invoice'));
    }

    public function filterOptions(Request $request)
    {
        $awal  = $request->awal;
        $akhir = $request->akhir;

        $base = SalesInvoice::query();
        if ($awal && $akhir) {
            $base->whereBetween('tanggal', [$awal, $akhir]);
        }

        // Distinct keys from invoices in range
        $customerIds   = (clone $base)->whereNotNull('company_profile_id')->distinct()->pluck('company_profile_id');
        $locationIds   = (clone $base)->whereNotNull('lokasi_id')->distinct()->pluck('lokasi_id');
        $salesGroupIds = (clone $base)->whereNotNull('sales_group_id')->distinct()->pluck('sales_group_id');

        // Fetch display names
        $customers   = CompanyProfile::whereIn('id', $customerIds)->orderBy('name')->get(['id', 'name']);
        $locations   = CompanyBranch::whereIn('id', $locationIds)->orderBy('name')->get(['id', 'name']);
        $salesGroups = SalesGroup::whereIn('id', $salesGroupIds)->orderBy('nama')->get(['id', 'nama']);



        return response()->json([
            'customers'    => $customers,
            'locations'    => $locations,
            'sales_groups' => $salesGroups,
        ]);
    }

    public function export(Request $request)
    {
        return Excel::download(
            new SalesInvoicesExport(
                awal: $request->periode_awal,
                akhir: $request->periode_akhir,
                customerId: $request->customer_id,
                lokasiId: $request->lokasi_id,
                salesGroupId: $request->sales_group_id
            ),
            'daftar_faktur_penjualan.xlsx'
        );
    }

    public function exportPdf(Request $request)
    {
        // Read filters from query or session fallback (if you stored them in datatable())
        $awal         = $request->input('periode_awal');
        $akhir        = $request->input('periode_akhir');
        $customerId   = $request->input('customer_id');
        $lokasiId     = $request->input('lokasi_id');
        $salesGroupId = $request->input('sales_group_id');

        // Memory-friendly SELECT with joins (no Eloquent relations)
        $q = DB::table('sales_invoices as si')
            ->leftJoin('company_profiles as cp', 'cp.id', '=', 'si.company_profile_id')
            ->leftJoin('sales_groups as sg', 'sg.id', '=', 'si.sales_group_id')
            ->select([
                'si.tanggal',
                'si.kode',
                DB::raw('COALESCE(cp.name, "-") as customer_name'),
                DB::raw('COALESCE(sg.nama, "-") as sales_name'),
                'si.jatuh_tempo',
                'si.grand_total',
                'si.total_retur',
                'si.total_bayar',
                'si.sisa_tagihan',
            ])
            ->when($awal && $akhir, fn($qq) => $qq->whereBetween('si.tanggal', [$awal.' 00:00:00', $akhir.' 23:59:59']))
            ->when($customerId, fn($qq) => $qq->where('si.company_profile_id', $customerId))
            ->when($lokasiId, fn($qq) => $qq->where('si.lokasi_id', $lokasiId))
            ->when($salesGroupId, fn($qq) => $qq->where('si.sales_group_id', $salesGroupId))
            ->orderBy('si.tanggal');

        // You can limit for very large PDFs (PDFs aren’t great for tens of thousands of rows)
        $rows = $q->get();

        $periodeText = ($awal && $akhir)
            ? date('d M Y', strtotime($awal)) . ' s/d ' . date('d M Y', strtotime($akhir))
            : '-';

        $total = $rows->sum('sisa_tagihan') ?? 0;

        $pdf = Pdf::loadView('sales.invoices.export_pdf', [
            'rows'        => $rows,
            'periodeText' => $periodeText,  
            'total'       => $total,
        ])->setPaper('a4', 'landscape'); // or 'landscape'

        // ->download() to force download, or ->stream() to preview in browser
        return $pdf->download('daftar_faktur_penjualan.pdf');
    }
}
