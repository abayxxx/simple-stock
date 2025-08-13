<?php

// app/Http/Controllers/SalesInvoiceController.php

namespace App\Http\Controllers\Sales;

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
            $query->whereBetween('tanggal', [$awal, $akhir]);
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
        $customers = CompanyProfile::orderBy('name')->get();
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
            'subtotal' => 'nullable|numeric',
            'grand_total' => 'nullable|numeric',
            'total_retur' => 'nullable|numeric',
            'total_bayar' => 'nullable|numeric',
            'sisa_tagihan' => 'nullable|numeric',
            'jatuh_tempo' => 'nullable|date',
            // Items array
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
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
                return back()->withInput()->withErrors(['kode' => 'Nomor Faktur harus diisi jika mode auto tidak dipilih']);
            }
        }

        // Check if diskon faktur and PPN are set
        if (!isset($data['diskon_faktur'])) {
            $data['diskon_faktur'] = 0;
        }
        if (!isset($data['diskon_ppn'])) {
            $data['diskon_ppn'] = 0;
        }

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($data, $items, &$invoice) {
            $invoice = SalesInvoice::create($data + ['user_id' => auth()->id()]);
            foreach ($items as $item) {
                // 1. Cek sisa stok PER BATCH
                $sisa = $this->getSisaStokBatch($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);


                if ($item['qty'] > $sisa) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.*.qty" => "Stok tidak cukup untuk produk {$item['product_id']} di seri/expired yang dipilih. Sisa: $sisa"
                    ]);
                }

                // 2. Buat invoice item
                $invoice->items()->create($item);

                // 3. Buat stok keluar
                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Penjualan (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0, // Diupdate setelah ini
                ]);
                // 4. Update sisa stok untuk batch ini
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

        $customers = CompanyProfile::orderBy('name')->get();
        $salesGroups = SalesGroup::orderBy('nama')->get();
        // load only products in purchases invoice
        $products = Product::whereIn('id', $invoice->items->pluck('product_id'))->orderBy('nama')->get();
        $branches = CompanyBranch::orderBy('name')->get();
        $invoice->load('items');
        return view('sales.invoices.edit', compact('invoice', 'customers', 'salesGroups', 'products', 'branches'));
    }

    public function update(Request $request, SalesInvoice $invoice)
    {

        //code...
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
            // Items array
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.lokasi_id' => 'nullable|integer',
            'items.*.qty' => 'required|numeric|min:1',
            'items.*.satuan' => 'nullable|string|max:20',
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


        // Check if diskon faktur and PPN are set
        if (!isset($data['diskon_faktur'])) {
            $data['diskon_faktur'] = 0;
        }
        if (!isset($data['diskon_ppn'])) {
            $data['diskon_ppn'] = 0;
        }

        $items = $data['items'];
        unset($data['items']);

        DB::transaction(function () use ($invoice, $data, $items) {
            $invoice->update($data + ['user_id' => auth()->id()]);

            // 1. Hapus semua invoice items lama dan stok out lama terkait faktur ini
            $oldItems = $invoice->items;
            foreach ($oldItems as $old) {
                // Hapus stok 'out' pada kombinasi yang sama (opsional: tambah invoice_id di tabel stok untuk lebih akurat)
                Stock::where([
                    'product_id'      => $old->product_id,
                    'type'            => 'out',
                    'no_seri'         => $old->no_seri,
                    'tanggal_expired' => $old->tanggal_expired,
                ])->delete();
                // Setelah delete, update sisa stok per batch
                self::updateAllSisaStok($old->product_id, $old->no_seri, $old->tanggal_expired);
            }
            $invoice->items()->delete();

            // 2. Update header
            $invoice->update($data);

            // 3. Buat item baru dan stok keluar baru
            foreach ($items as $item) {
                $sisa = $this->getSisaStokBatch($item['product_id'],  $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
                if ($item['qty'] > $sisa) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "items.*.qty" => "Stok tidak cukup untuk produk {$item['product_id']} di lokasi/seri/expired yang dipilih. Sisa: $sisa"
                    ]);
                }

                $invoice->items()->create($item);

                Stock::create([
                    'product_id'       => $item['product_id'],
                    'type'             => 'out',
                    'jumlah'           => $item['qty'],
                    'no_seri'          => $item['no_seri'] ?? null,
                    'tanggal_expired'  => $item['tanggal_expired'] ?? null,
                    'harga_net'        => $item['harga_satuan'],
                    'catatan'          => "Penjualan (Faktur: {$invoice->kode})" . (isset($item['catatan']) ? " - {$item['catatan']}" : ''),
                    'sisa_stok'        => 0,
                ]);
                self::updateAllSisaStok($item['product_id'], $item['no_seri'] ?? null, $item['tanggal_expired'] ?? null);
            }
        });

        return redirect()->route('sales.invoices.index')->with('success', 'Faktur penjualan berhasil diupdate.');
    }

    public function destroy(SalesInvoice $invoice)
    {
        //Hapus semua item terkait
        $invoice->items()->delete();
        //Hapus faktur
        $invoice->delete();
        return redirect()->route('sales.invoices.index')->with('success', 'Faktur penjualan berhasil dihapus.');
    }

    protected static function generateKode()
    {
        $prefix = 'SI.' . date('ym') . '.';
        $last = SalesInvoice::where('kode', 'like', $prefix . '%')->max('kode');
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
        // if ($no_seri) $query->where('no_seri', $no_seri);
        // if ($tanggal_expired) $query->where('tanggal_expired', $tanggal_expired);

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
}
