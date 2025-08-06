@extends('adminlte::page')

@section('title', 'Dashboard')

@section('content_header')
<h1>DASHBOARD</h1>
@stop

@section('content')
<div class="row">
    <!-- Info boxes -->
    <div class="col-md-3 col-sm-4 col-6">
        <div class="info-box bg-info">
            <span class="info-box-icon"><i class="fas fa-users"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Customer</span>
                <span class="info-box-number">{{ number_format($totalCustomer) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="info-box bg-warning">
            <span class="info-box-icon"><i class="fas fa-phone"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Call Customer Hari Ini</span>
                <span class="info-box-number">{{ $totalCallCustomerHariIni }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="info-box bg-success">
            <span class="info-box-icon"><i class="fas fa-box"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Total Produk</span>
                <span class="info-box-number">{{ number_format($totalProduk) }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3 col-sm-4 col-6">
        <div class="info-box bg-primary">
            <span class="info-box-icon"><i class="fas fa-bullhorn"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Call Produk Hari Ini</span>
                <span class="info-box-number">{{ $totalCallProdukHariIni }}</span>
            </div>
        </div>
    </div>

</div>
<div class="row">
    <div class="col-md-3">
        <div class="info-box bg-danger">
            <span class="info-box-icon"><i class="fas fa-shopping-cart"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Penjualan Hari Ini</span>
                <span class="info-box-number">{{ number_format($penjualanHariIni, 0, ',', '.') }}</span>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="info-box bg-teal">
            <span class="info-box-icon"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tanda Terima Hari Ini</span>
                <span class="info-box-number">{{ $tandaTerimaHariIni }}</span>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-sm-4 col-6">
        <div class="info-box bg-secondary">
            <span class="info-box-icon"><i class="fas fa-calendar-day"></i></span>
            <div class="info-box-content">
                <span class="info-box-text">Tanggal Hari Ini</span>
                <span class="info-box-number">{{ \Carbon\Carbon::parse($today)->format('d M Y') }}</span>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <!-- Faktur Jatuh Tempo -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Faktur Jatuh Tempo</div>
            <div class="card-body p-2">
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr>
                            <th>NAMA CUSTOMER</th>
                            <th>TGL.</th>
                            <th>JTH. TEMPO</th>
                            <th>JLH.</th>
                            <th>TOTAL NILAI</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($fakturJatuhTempo as $f)
                        <tr>
                            <td>{{ $f->customer->name ?? '-' }}</td>
                            <td>{{ tanggal_indo($f->tanggal) }}</td>
                            <td>{{ tanggal_indo($f->jatuh_tempo) }}</td>
                            <td>1</td>
                            <td>{{ number_format($f->sisa_tagihan, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <!-- Top Produk -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">Top 20 Produk Bulan Ini</div>
            <div class="card-body p-2">
                <table class="table table-bordered table-sm table-striped">
                    <thead>
                        <tr>
                            <th>KODE PRODUK</th>
                            <th>NAMA PRODUK</th>
                            <th>TOTAL UNIT</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($topProdukBulan as $t)
                        <tr>
                            <td>{{ $t->product->kode ?? '' }}</td>
                            <td>{{ $t->product->nama ?? '' }}</td>
                            <td>{{ number_format($t->total_unit, 0, ',', '.') }}</td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<!-- Grafik Penjualan -->
<div class="row">
    <div class="col-12">
        <div class="card card-primary">
            <div class="card-header">Grafik Penjualan Bulan Ini</div>
            <div class="card-body">
                <canvas id="penjualanChart" height="110"></canvas>
            </div>
        </div>
    </div>
</div>
@stop

@push('js')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    const chartData = @json($chartData);
    const ctx = document.getElementById('penjualanChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: chartData.map(d => d.tanggal),
            datasets: [{
                label: 'Total Penjualan',
                data: chartData.map(d => d.total),
                fill: true,
                borderColor: '#FFC107', // orange-yellow line
                backgroundColor: 'rgba(255, 193, 7, 0.3)', // area fill
                tension: 0.3, // smooth line
                pointRadius: 2,
                pointBackgroundColor: '#FFC107',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            // Format as currency
                            return value.toLocaleString('id-ID', {
                                minimumFractionDigits: 0
                            });
                        }
                    }
                }
            }
        }
    });
</script>
@endpush