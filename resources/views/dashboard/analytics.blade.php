@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div class="d-flex align-items-center">
        <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i> 戻る
        </a>
        <h1 class="h2 mb-0">グラフ分析</h1>
    </div>
    <div class="btn-group">
        <a href="{{ route('dashboard.analytics', ['range' => 'today']) }}" class="btn {{ $currentRange == 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">今日</a>
        <a href="{{ route('dashboard.analytics', ['range' => 'month']) }}" class="btn {{ $currentRange == 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">今月</a>
        <a href="{{ route('dashboard.analytics', ['range' => 'all']) }}" class="btn {{ $currentRange == 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">今年</a>
    </div>
</div>

<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card h-100">
            <div class="card-header">月別売上 (過去12ヶ月)</div>
            <div class="card-body"><canvas id="monthlySalesChart" height="350"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">店舗別シェア ({{ $dateRangeLabel }})</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="storeShareChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">商品別売上Top5 ({{ $dateRangeLabel }})</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="productSalesChart" height="300"></canvas></div>
        </div>
    </div>
    <div class="col-lg-4 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-header">曜日別売上 ({{ $dateRangeLabel }})</div>
            <div class="card-body d-flex align-items-center justify-content-center"><canvas id="dayOfWeekChart" height="300"></canvas></div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        'use strict';
        
        // Chart.js Datalabels Pluginをグローバルに登録
        Chart.register(ChartDataLabels);

        // Chart.jsの共通設定
        Chart.defaults.font.family = "'Helvetica Neue', 'Helvetica', 'Arial', sans-serif";
        const chartColors = ['#0d6efd', '#6f42c1', '#d63384', '#fd7e14', '#198754', '#20c997', '#0dcaf0'];

        // --- ここから修正 ---
        // パーセンテージ計算と表示を制御する共通フォーマッタ
        const percentageFormatter = {
            formatter: (value, ctx) => {
                // データセットの合計値を正しく計算
                const sum = ctx.chart.data.datasets[0].data.reduce((a, b) => a + b, 0);
                if (sum === 0) return '';
                
                const percentage = (value * 100 / sum);
                // 5%未満のラベルは表示しない
                if (percentage < 5) {
                    return '';
                }
                return percentage.toFixed(1) + '%';
            },
            color: (context) => {
                // 背景色に合わせて文字色を白か黒に自動調整
                const bgColor = context.dataset.backgroundColor[context.dataIndex];
                // 簡単な輝度計算
                const color = (bgColor.charAt(0) === '#') ? bgColor.substring(1, 7) : bgColor;
                const r = parseInt(color.substring(0, 2), 16);
                const g = parseInt(color.substring(2, 4), 16);
                const b = parseInt(color.substring(4, 6), 16);
                return (((r * 0.299) + (g * 0.587) + (b * 0.114)) > 186) ? '#000' : '#fff';
            },
            font: {
                weight: 'bold'
            }
        };
        // --- ここまで修正 ---

        // 1. 月別売上 (棒グラフ)
        const monthlyCtx = document.getElementById('monthlySalesChart');
        const monthlyData = @json($monthlySalesChart);
        if (monthlyCtx) {
            new Chart(monthlyCtx, {
                type: 'bar',
                data: {
                    labels: monthlyData.map(row => new Date(row.month + '-01').toLocaleDateString('ja-JP', { year: '2-digit', month: 'short' })),
                    datasets: [{
                        label: '売上高',
                        data: monthlyData.map(row => row.total_revenue),
                        backgroundColor: 'rgba(54, 162, 235, 0.6)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false, 
                    scales: { y: { beginAtZero: true } },
                    plugins: { datalabels: { display: false } }
                }
            });
        }
        
        // 2. 店舗別シェア (円グラフ)
        const storeCtx = document.getElementById('storeShareChart');
        const storeData = @json($storeShareChart);
        if (storeCtx && storeData.length > 0) {
            new Chart(storeCtx, {
                type: 'pie',
                data: {
                    labels: storeData.map(row => row.name),
                    datasets: [{ data: storeData.map(row => row.total_revenue), backgroundColor: chartColors }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: percentageFormatter // 修正したフォーマッタを適用
                    }
                }
            });
        }

        // 3. 商品別売上Top5 (ドーナツグラフ)
        const productCtx = document.getElementById('productSalesChart');
        const productData = @json($productSalesChart);
        if (productCtx && productData.length > 0) {
            new Chart(productCtx, {
                type: 'doughnut',
                data: {
                    labels: productData.map(row => row.name),
                    datasets: [{ data: productData.map(row => row.total_revenue), backgroundColor: chartColors.slice().reverse() }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: {
                        datalabels: percentageFormatter // 修正したフォーマッタを適用
                    }
                }
            });
        }
        
        // 4. 曜日別売上 (レーダーチャート)
        const dayOfWeekCtx = document.getElementById('dayOfWeekChart');
        const dayOfWeekData = @json($dayOfWeekData);
        if (dayOfWeekCtx && dayOfWeekData.data.some(d => d > 0)) {
            new Chart(dayOfWeekCtx, {
                type: 'radar',
                data: {
                    labels: dayOfWeekData.labels,
                    datasets: [{
                        label: '売上高',
                        data: dayOfWeekData.data,
                        fill: true,
                        backgroundColor: 'rgba(255, 99, 132, 0.2)',
                        borderColor: 'rgb(255, 99, 132)',
                        pointBackgroundColor: 'rgb(255, 99, 132)',
                    }]
                },
                options: { 
                    responsive: true, 
                    maintainAspectRatio: false,
                    plugins: { datalabels: { display: false } }
                }
            });
        }

    })();
</script>
@endpush
@endsection