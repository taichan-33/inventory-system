@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h1 class="h2 mb-1">AI 需要予測</h1>
                    <p class="text-muted">在庫が発注点を下回った商品を対象に、バッチで需要予測と発注推奨を行います。</p>
                </div>
            </div>

            @if (session('info'))
                <div class="alert alert-info">{{ session('info') }}</div>
            @endif
            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <div class="text-center p-4 border rounded bg-light">
                <form method="POST" action="{{ route('forecast.run') }}" id="forecast-form">
                    @csrf
                    <button type="submit" class="btn btn-primary btn-lg" id="forecast-button">
                        <i class="bi bi-magic me-2"></i>AI需要予測バッチを実行
                    </button>
                </form>
                <div id="loading-indicator" class="mt-3" style="display: none;">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted">AIが予測を計算中です。しばらくお待ちください...</p>
                </div>
            </div>

            @if (isset($result) && !empty($result))
                <div class="mt-5">
                    <h2 class="h4">予測結果</h2>
                    
                    {{-- グラフ表示 --}}
                    <div class="card mb-4">
                        <div class="card-header">
                            推奨発注数 上位5件
                        </div>
                        <div class="card-body">
                            <canvas id="forecastChart" height="300"></canvas>
                        </div>
                    </div>

                    {{-- テーブル表示 --}}
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>商品名</th>
                                    <th>店舗名</th>
                                    <th class="text-center">現在在庫</th>
                                    <th class="text-center">7日間売り上げ予測</th>
                                    <th class="text-center bg-primary text-white">推奨発注数</th>
                                    <th>予測の根拠</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($result as $item)
                                <tr>
                                    <td>{{ $item['product_name'] }}</td>
                                    <td>{{ $item['store_name'] }}</td>
                                    <td class="text-center">{{ $item['current_stock'] }}</td>
                                    <td class="text-center">{{ $item['predicted_sales_7d'] }}</td>
                                    <td class="text-center fw-bold fs-5 text-primary">{{ $item['recommended_order'] }}</td>
                                    <td class="small text-muted">{{ $item['reasoning'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.getElementById('forecast-form').addEventListener('submit', function() {
        document.getElementById('forecast-button').style.display = 'none';
        document.getElementById('loading-indicator').style.display = 'block';
    });

    @if (isset($result) && !empty($result))
    (function() {
        'use strict';
        const forecastCtx = document.getElementById('forecastChart');
        const forecastData = @json($result);

        // 推奨発注数でソートし、上位5件を取得
        const top5Data = forecastData.sort((a, b) => b.recommended_order - a.recommended_order).slice(0, 5);

        if (forecastCtx) {
            new Chart(forecastCtx, {
                type: 'bar',
                data: {
                    labels: top5Data.map(row => `${row.product_name.substring(0, 10)}... (${row.store_name})`),
                    datasets: [{
                        label: '推奨発注数',
                        data: top5Data.map(row => row.recommended_order),
                        backgroundColor: 'rgba(13, 110, 253, 0.7)',
                        borderColor: 'rgba(13, 110, 253, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    indexAxis: 'y', // 横棒グラフにする
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: { x: { beginAtZero: true } },
                    plugins: { legend: { display: false } }
                }
            });
        }
    })();
    @endif
</script>
@endpush