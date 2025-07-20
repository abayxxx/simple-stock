@extends('adminlte::page')

@section('title', 'Lihat Profil Perusahaan')

@section('content_header')
<h1>Detail Profil Perusahaan</h1>
@stop

@section('content')
<a href="{{ route('company_profiles.index') }}" class="btn btn-secondary mb-2">Kembali</a>
<div class="card">
    <div class="card-header">
        {{ $company_profile->name }} ({{ $company_profile->code }})
    </div>
    <div class="card-body">
        <strong>Relasi:</strong> {{ ucfirst($company_profile->relationship) }}<br>
        <strong>Alamat:</strong> {{ $company_profile->address }}<br>
        <strong>Lokasi Spesifik:</strong> {{ $company_profile->spesific_location }}<br>
        <strong>Telepon:</strong> {{ $company_profile->phone }}<br>
        <strong>Email:</strong> {{ $company_profile->email }}<br>
        <strong>Website:</strong> {{ $company_profile->website }}<br>
        <strong>NPWP:</strong> {{ $company_profile->npwp }}<br>
        <strong>Nama Faktur Pajak:</strong> {{ $company_profile->tax_invoice_to }}<br>
        <strong>Alamat Faktur Pajak:</strong> {{ $company_profile->tax_invoice_address }}<br>
    </div>
    @if($company_profile->externalData)
    <div class="card-footer">
        <h4>Data Eksternal</h4>
        <table class="table">
            <tr>
                <th>Total Piutang Saat Ini</th>
                <td>{{ number_format($company_profile->externalData->total_receivable_now,2) }}</td>
            </tr>
            <tr>
                <th>Jlh. Faktur Penjualan Belum Lunas</th>
                <td>{{ $company_profile->externalData->unpaid_sales_invoices_count }}</td>
            </tr>
            <tr>
                <th>Tgl. Penjualan Terakhir</th>
                <td>{{ $company_profile->externalData->last_sales_date }}</td>
            </tr>
            <tr>
                <th>Giro Terima</th>
                <td>{{ number_format($company_profile->externalData->giro_received,2) }}</td>
            </tr>
            <tr>
                <th>Piutang Jatuh Tempo</th>
                <td>{{ number_format($company_profile->externalData->due_receivables,2) }}</td>
            </tr>
            <tr>
                <th>Jlh. Faktur Penjualan Jatuh Tempo</th>
                <td>{{ $company_profile->externalData->due_sales_invoices_count }}</td>
            </tr>
            <tr>
                <th>Grand Total Penjualan</th>
                <td>{{ number_format($company_profile->externalData->grand_total_sales,2) }}</td>
            </tr>
            <tr>
                <th>Grand Total Retur Penjualan</th>
                <td>{{ number_format($company_profile->externalData->grand_total_sales_returns,2) }}</td>
            </tr>
            <tr>
                <th>Total Hutang Saat Ini</th>
                <td>{{ number_format($company_profile->externalData->total_debt_now,2) }}</td>
            </tr>
            <tr>
                <th>Jlh. Faktur Pembelian Belum Lunas</th>
                <td>{{ $company_profile->externalData->unpaid_purchase_invoices_count }}</td>
            </tr>
            <tr>
                <th>Tgl. Pembelian Terakhir</th>
                <td>{{ $company_profile->externalData->last_purchase_date }}</td>
            </tr>
            <tr>
                <th>Giro Bayar</th>
                <td>{{ number_format($company_profile->externalData->giro_paid,2) }}</td>
            </tr>
            <tr>
                <th>Hutang Jatuh Tempo</th>
                <td>{{ number_format($company_profile->externalData->due_debt,2) }}</td>
            </tr>
            <tr>
                <th>Jlh. Faktur Pembelian Jatuh Tempo</th>
                <td>{{ $company_profile->externalData->due_purchase_invoices_count }}</td>
            </tr>
            <tr>
                <th>Grand Total Pembelian</th>
                <td>{{ number_format($company_profile->externalData->grand_total_purchases,2) }}</td>
            </tr>
            <tr>
                <th>Grand Total Retur Pembelian</th>
                <td>{{ number_format($company_profile->externalData->grand_total_purchase_returns,2) }}</td>
            </tr>
        </table>
    </div>
    @endif
</div>
@stop