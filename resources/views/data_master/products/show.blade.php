@extends('adminlte::page')

@section('title', 'Detail Produk')

@section('content_header')
<h1>Detail Produk</h1>
<a href="{{ route('products.index') }}" class="btn btn-secondary mb-3">Kembali ke Daftar</a>
@stop

@section('content')
<ul class="nav nav-tabs" id="tabProduk" role="tablist">
    <li class="nav-item">
        <a class="nav-link active" id="umum-tab" data-toggle="tab" href="#umum" role="tab">Data Umum</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="pokok-tab" data-toggle="tab" href="#pokok" role="tab">Harga Pokok</a>
    </li>
    <li class="nav-item">
        <a class="nav-link" id="jual-tab" data-toggle="tab" href="#jual" role="tab">Harga Jual</a>
    </li>
</ul>
<div class="tab-content mt-3">
    {{-- Data Umum --}}
    <div class="tab-pane fade show active" id="umum" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>Kode Produk</th>
                        <td>{{ $product->kode }}</td>
                    </tr>
                    <tr>
                        <th>Nama Produk</th>
                        <td>{{ $product->nama }}</td>
                    </tr>
                    <tr>
                        <th>Satuan Kecil</th>
                        <td>{{ $product->isi_satuan_kecil }} {{ $product->satuan_kecil }}</td>
                    </tr>
                    <tr>
                        <th>Satuan Sedang</th>
                        <td>
                            @if($product->satuan_sedang)
                            {{ $product->isi_satuan_sedang }} {{ $product->satuan_sedang }}
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Satuan Besar</th>
                        <td>
                            @if($product->satuan_besar)
                            {{ $product->isi_satuan_besar }} {{ $product->satuan_besar }}
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Satuan Massa</th>
                        <td>
                            @if($product->satuan_massa)
                            {{ $product->isi_satuan_massa }} {{ $product->satuan_massa }}
                            @else
                            -
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <th>Catatan</th>
                        <td>{{ $product->catatan ?: '-' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
    {{-- Harga Pokok --}}
    <div class="tab-pane fade" id="pokok" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>HPP Bruto (Kecil)</th>
                        <td>{{ number_format($product->hpp_bruto_kecil,2,',','.') ?: '-' }}</td>
                    </tr>
                    <tr>
                        <th>HPP Bruto (Besar)</th>
                        <td>{{ number_format($product->hpp_bruto_besar,2,',','.') ?: '-' }}</td>
                    </tr>
                    @for($i=1;$i<=5;$i++)
                        <tr>
                        <th>Diskon HPP {{ $i }}</th>
                        <td>{{ number_format($product->{'diskon_hpp_'.$i},2,',','.') ?: '-' }}</td>
                        </tr>
                        @endfor
                </table>
            </div>
        </div>
    </div>
    {{-- Harga Jual --}}
    <div class="tab-pane fade" id="jual" role="tabpanel">
        <div class="row">
            <div class="col-md-6">
                <table class="table">
                    <tr>
                        <th>Harga Jual Umum</th>
                        <td>{{ number_format($product->harga_umum,2,',','.') ?: '-' }}</td>
                    </tr>
                    @for($i=1;$i<=5;$i++)
                        <tr>
                        <th>Diskon Harga {{ $i }}</th>
                        <td>{{ number_format($product->{'diskon_harga_'.$i},2,',','.') ?: '-' }}</td>
                        </tr>
                        @endfor
                </table>
            </div>
        </div>
    </div>
</div>
@stop