<?php

namespace App\Http\Controllers\Stocks;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{
    public function indexIn()
    {
        return $this->index('in');
    }
    public function indexOut()
    {
        return $this->index('out');
    }
    public function indexDestroy()
    {
        return $this->index('destroy');
    }

    private function index($type)
    {
        $title = $this->getTitle($type);
        return view("stocks.$type.index", compact('type', 'title'));
    }

    public function datatable($type)
    {
        $stocks = Stock::with('product')->where('type', $type);
        return DataTables::of($stocks)
            ->editColumn('tanggal', fn($row) => $row->created_at->format('d M Y'))
            ->addColumn('status', fn($row) => 'Aktif')
            ->addColumn('jumlah_item', fn($row) => 1)
            ->editColumn('jumlah', fn($row) => number_format($row->jumlah, 2, ',', '.'))
            ->editColumn('subtotal', fn($row) => number_format($row->subtotal, 2, ',', '.'))
            ->addColumn('aksi', function ($row) use ($type) {
                return view("stocks.partials.aksi", compact('row', 'type'))->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }

    public function create($type)
    {
        $products = Product::orderBy('nama')->get();
        $title = $this->getTitle($type);
        return view("stocks.$type.create", compact('type', 'products', 'title'));
    }

    public function store(Request $request, $type)
    {
        $data = $this->validateStock($request);
        $data['type'] = $type;

        // Validasi stok tidak boleh minus (khusus 'out' dan 'destroy')
        if (in_array($type, ['out', 'destroy'])) {
            $sisa = $this->calculateSisaStok($data['product_id']);
            if ($data['jumlah'] > $sisa) {
                return back()
                    ->withInput()
                    ->withErrors(['jumlah' => 'Jumlah yang dimasukkan melebihi stok tersedia (' . $sisa . ').']);
            }
        }

        DB::transaction(function () use ($data, &$stock) {
            $stock = Stock::create($data);
            $stock->sisa_stok = $this->calculateSisaStok($stock->product_id);
            $stock->save();
        });
        return redirect()->route("stock.$type")->with('success', 'Transaksi stok berhasil ditambahkan.');
    }


    public function edit($type, Stock $stock)
    {
        $products = Product::orderBy('nama')->get();
        $title = $this->getTitle($type);
        return view("stocks.$type.edit", compact('type', 'stock', 'products', 'title'));
    }

    public function update(Request $request, $type, Stock $stock)
    {
        $data = $this->validateStock($request);

        // Validasi stok tidak boleh minus (khusus 'out' dan 'destroy')
        if (in_array($type, ['out', 'destroy'])) {
            $sisa = $this->calculateSisaStok($data['product_id']);
            // Tambahkan jumlah stok lama (karena stok lama akan diupdate)
            $sisa = $sisa + $stock->jumlah;
            if ($data['jumlah'] > $sisa) {
                return back()
                    ->withInput()
                    ->withErrors(['jumlah' => 'Jumlah yang dimasukkan melebihi stok tersedia (' . $sisa . ').']);
            }
        }

        $stock->update($data);
        $stock->sisa_stok = $this->calculateSisaStok($stock->product_id);
        $stock->save();
        return redirect()->route("stock.$type")->with('success', 'Transaksi stok berhasil diupdate.');
    }


    public function delete($type, Stock $stock)
    {
        $stock->delete();
        // Update sisa stok produk terkait
        $this->updateAllSisaStok($stock->product_id);
        return redirect()->route("stock.$type")->with('success', 'Transaksi stok berhasil dihapus.');
    }

    public function show($type, Stock $stock)
    {
        $title = $this->getTitle($type);
        return view("stocks.$type.show", compact('type', 'stock', 'title'));
    }

    // === Helper
    private function validateStock(Request $request)
    {
        return $request->validate([
            'product_id' => 'required|exists:products,id',
            'no_seri' => 'nullable|string|max:100',
            'tanggal_expired' => 'nullable|date',
            'jumlah' => 'required|integer|min:1',
            'harga_net' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'catatan' => 'nullable|string|max:500',
        ]);
    }

    // Hitung ulang sisa stok setelah transaksi
    public function calculateSisaStok($product_id)
    {

        $in     = Stock::where('product_id', $product_id)->where('type', 'in')->sum('jumlah');
        $out    = Stock::where('product_id', $product_id)->where('type', 'out')->sum('jumlah');
        $destroy = Stock::where('product_id', $product_id)->where('type', 'destroy')->sum('jumlah');
        return $in - $out - $destroy;
    }
    // Untuk update semua sisa_stok (misal setelah hapus transaksi)
    private function updateAllSisaStok($product_id)
    {
        $stocks = Stock::where('product_id', $product_id)->orderBy('id')->get();
        foreach ($stocks as $stock) {
            $stock->sisa_stok = $this->calculateSisaStok($product_id);
            $stock->save();
        }
    }
    private function getTitle($type)
    {
        return $type == 'in' ? 'Stok Masuk' : ($type == 'out' ? 'Stok Keluar' : 'Stok Pemusnahan');
    }
}
