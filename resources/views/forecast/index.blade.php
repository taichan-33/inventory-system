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

            {{-- 各種メッセージ表示エリア --}}
            @if (session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
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
                {{-- 複数発注用のフォーム --}}
                <form method="POST" action="{{ route('purchase-orders.store') }}" id="order-form">
                    @csrf
                    <div class="mt-5">
                        <h2 class="h4">予測結果</h2>
                        
                        <div class="card mb-4">
                            <div class="card-header">
                                推奨発注数 上位5件
                            </div>
                            <div class="card-body">
                                <canvas id="forecastChart" height="300"></canvas>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-center"><input class="form-check-input" type="checkbox" id="select-all-checkbox"></th>
                                        <th>商品名</th>
                                        <th>店舗名</th>
                                        <th class="text-center">現在在庫</th>
                                        <th class="text-center">7日間売り上げ予測</th>
                                        <th class="text-center bg-primary text-white">推奨発注数</th>
                                        <th>予測の根拠</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($result as $index => $item)
                                        {{-- 発注数が1以上の商品のみ表示 --}}
                                        @if($item['recommended_order'] > 0)
                                        <tr>
                                            {{-- 各行のチェックボックスとhidden input --}}
                                            <td class="text-center">
                                                <input class="form-check-input order-checkbox" type="checkbox" name="orders[{{ $index }}][product_id]" value="{{ $item['product_id'] }}"
                                                       data-product-name="{{ $item['product_name'] }}"
                                                       data-store-name="{{ $item['store_name'] }}"
                                                       data-quantity="{{ $item['recommended_order'] }}">
                                                <input type="hidden" name="orders[{{ $index }}][store_id]" value="{{ $item['store_id'] }}">
                                                <input type="hidden" name="orders[{{ $index }}][quantity]" value="{{ $item['recommended_order'] }}">
                                            </td>
                                            <td>{{ $item['product_name'] }}</td>
                                            <td>{{ $item['store_name'] }}</td>
                                            <td class="text-center">{{ $item['current_stock'] }}</td>
                                            <td class="text-center">{{ $item['predicted_sales_7d'] }}</td>
                                            <td class="text-center fw-bold fs-5 text-primary">{{ $item['recommended_order'] }}</td>
                                            <td class="small text-muted">{{ $item['reasoning'] }}</td>
                                        </tr>
                                        @endif
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        
                        {{-- 一括発注ボタン --}}
                        <div class="d-flex justify-content-end mt-3">
                            <button type="button" class="btn btn-success btn-lg" id="order-confirm-button" data-bs-toggle="modal" data-bs-target="#orderConfirmModal" disabled>
                                <i class="bi bi-cart-plus-fill me-2"></i>選択した商品を発注する
                            </button>
                        </div>
                    </div>
                </form>
            @endif
        </div>
    </div>
</div>

<!-- 発注確認モーダル -->
<div class="modal fade" id="orderConfirmModal" tabindex="-1" aria-labelledby="orderConfirmModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="orderConfirmModalLabel">発注内容の確認</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p>以下の商品を発注します。よろしいですか？</p>
        <div id="order-confirm-list"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">キャンセル</button>
        <button type="button" class="btn btn-primary" id="submit-order-button">はい、発注します</button>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- ローディング表示とグラフ描画のスクリプト --}}
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

        const top5Data = forecastData
            .filter(item => item.recommended_order > 0)
            .sort((a, b) => b.recommended_order - a.recommended_order)
            .slice(0, 5);

        if (forecastCtx && top5Data.length > 0) {
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
                    indexAxis: 'y',
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

{{-- 複数選択とモーダル制御用スクリプト --}}
<script>
document.addEventListener('DOMContentLoaded', function () {
    const orderForm = document.getElementById('order-form');
    if (!orderForm) return;

    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    const orderCheckboxes = document.querySelectorAll('.order-checkbox');
    const orderConfirmButton = document.getElementById('order-confirm-button');
    const orderConfirmList = document.getElementById('order-confirm-list');
    const submitOrderButton = document.getElementById('submit-order-button');

    function toggleOrderButton() {
        const checkedCount = document.querySelectorAll('.order-checkbox:checked').length;
        orderConfirmButton.disabled = checkedCount === 0;
    }

    orderCheckboxes.forEach(checkbox => {
        const parentRow = checkbox.closest('tr');
        parentRow.querySelectorAll('input[type="hidden"]').forEach(input => input.disabled = true);
    });

    selectAllCheckbox.addEventListener('change', function () {
        orderCheckboxes.forEach(checkbox => {
            if (!checkbox.disabled) {
                checkbox.checked = this.checked;
                const parentRow = checkbox.closest('tr');
                parentRow.querySelectorAll('input[type="hidden"]').forEach(input => input.disabled = !this.checked);
            }
        });
        toggleOrderButton();
    });

    orderCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function () {
            const parentRow = checkbox.closest('tr');
            parentRow.querySelectorAll('input[type="hidden"]').forEach(input => input.disabled = !this.checked);
            
            const allChecked = Array.from(orderCheckboxes).every(cb => cb.checked || cb.disabled);
            selectAllCheckbox.checked = allChecked;
            
            toggleOrderButton();
        });
    });

    orderConfirmButton.addEventListener('click', function () {
        const selectedItems = [];
        document.querySelectorAll('.order-checkbox:checked').forEach(checkbox => {
            selectedItems.push({
                productName: checkbox.dataset.productName,
                storeName: checkbox.dataset.storeName,
                quantity: checkbox.dataset.quantity
            });
        });
        
        let listHtml = '<ul class="list-group">';
        selectedItems.forEach(item => {
            listHtml += `<li class="list-group-item d-flex justify-content-between align-items-center">
                            <div><strong>${item.productName}</strong><br><small class="text-muted">${item.storeName}</small></div>
                            <span class="badge bg-primary rounded-pill fs-6">${item.quantity}個</span>
                         </li>`;
        });
        listHtml += '</ul>';
        orderConfirmList.innerHTML = listHtml;
    });

    submitOrderButton.addEventListener('click', function () {
        orderForm.submit();
    });
});
</script>
@endpush

