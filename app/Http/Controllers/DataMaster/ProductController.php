<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProductController extends Controller
{


    public function index()
    {
        return view('data_master.products.index');
    }

    public function datatable()
    {
        return DataTables::of(Product::query())
            ->editColumn('hpp_bruto_kecil', fn($row) => number_format($row->hpp_bruto_kecil, 2, ',', '.'))
            ->editColumn('harga_umum', fn($row) => number_format($row->harga_umum, 2, ',', '.'))
            ->addColumn('aksi', function ($row) {
                return view('data_master.products.partials.aksi', compact('row'))->render();
            })
            ->rawColumns(['aksi'])
            ->make(true);
    }


    public function create()
    {
        $satuanList = Product::getSatuanList();
        $satuanMassaList = Product::getSatuanMassaList();
        return view('data_master.products.create', compact('satuanList', 'satuanMassaList'));
    }

    public function store(Request $request)
    {
        $data = $this->validateProduct($request);

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product)
    {
        $product->load('stocks'); // Load stok terkait
        return view('data_master.products.show', compact('product'));
    }

    public function edit(Product $product)
    {

        $satuanList = $product->getSatuanList();
        $satuanMassaList = $product->getSatuanMassaList();
        return view('data_master.products.edit', compact('product', 'satuanList', 'satuanMassaList'));
    }

    public function update(Request $request, Product $product)
    {
        $data = $this->validateProduct($request, $product->id);

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diupdate.');
    }

    public function destroy(Product $product)
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk berhasil dihapus.');
    }

    private function validateProduct(Request $request, $id = null)
    {

        try {
            $data = $request->validate([
                'nama' => 'required|string|max:255',
                'merk' => 'nullable|string|max:100',
                'satuan_kecil' => 'required|string|max:100',
                'isi_satuan_kecil' => 'required|integer|min:1',
                'satuan_sedang' => 'nullable|string|max:100',
                'isi_satuan_sedang' => 'nullable|integer|min:1',
                'satuan_besar' => 'nullable|string|max:100',
                'isi_satuan_besar' => 'nullable|integer|min:1',
                'satuan_massa' => 'nullable|string|max:100',
                'isi_satuan_massa' => 'nullable|integer|min:1',
                'catatan' => 'nullable|string|max:500',
                'hpp_bruto_kecil' => 'nullable|numeric',
                'hpp_bruto_besar' => 'nullable|numeric',
                'diskon_hpp_1' => 'nullable|numeric',
                'diskon_hpp_2' => 'nullable|numeric',
                'diskon_hpp_3' => 'nullable|numeric',
                'diskon_hpp_4' => 'nullable|numeric',
                'diskon_hpp_5' => 'nullable|numeric',
                'harga_umum' => 'nullable|numeric',
                'diskon_harga_1' => 'nullable|numeric',
                'diskon_harga_2' => 'nullable|numeric',
                'diskon_harga_3' => 'nullable|numeric',
                'diskon_harga_4' => 'nullable|numeric',
                'diskon_harga_5' => 'nullable|numeric',
            ]);
        } catch (\Throwable $th) {
            //throw $th;
            return redirect()->back()->withErrors($th->getMessage())->withInput();
        }

        return $data;
    }

    public function search(Request $request)
    {
        $q = $request->get('q', '');
        $products = Product::where('kode', 'like', "%$q%")
            ->orWhere('nama', 'like', "%$q%")
            ->orderBy('kode')
            ->limit(20)
            ->get(['id', 'kode', 'nama', 'satuan_kecil']);
        return response()->json($products);
    }
}
