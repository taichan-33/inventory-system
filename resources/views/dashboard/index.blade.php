@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap">
    <div class="d-flex align-items-center mb-2 mb-md-0">
        <h1 class="h2 me-3 mb-0">ダッシュボード</h1>
        <a href="{{ route('dashboard.analytics') }}" class="btn btn-outline-primary">
            <i class="bi bi-graph-up-arrow me-1"></i>グラフ分析
        </a>
    </div>
    <div class="d-flex align-items-center">
        <div class="btn-group me-2">
            <a href="{{ route('dashboard', ['range' => 'today']) }}" class="btn {{ $currentRange == 'today' ? 'btn-primary' : 'btn-outline-secondary' }}">今日</a>
            <a href="{{ route('dashboard', ['range' => 'month']) }}" class="btn {{ $currentRange == 'month' ? 'btn-primary' : 'btn-outline-secondary' }}">今月</a>
            <a href="{{ route('dashboard', ['range' => 'all']) }}" class="btn {{ $currentRange == 'all' ? 'btn-primary' : 'btn-outline-secondary' }}">今年</a>
        </div>
        <form id="date-range-form" class="d-flex align-items-center">
            <input type="hidden" name="range" value="custom">
            <input type="text" id="date-range-picker" class="form-control" placeholder="詳細期間を選択" 
                   value="{{ $startDate && $endDate ? $startDate . ' to ' . $endDate : '' }}">
            <button type="submit" class="btn btn-primary ms-2">適用</button>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-6 mb-3">
        <div class="card kpi-card">
            <div class="card-body">
                <div class="kpi-icon bg-primary"><i class="bi bi-currency-yen"></i></div>
                <div>
                    <h5 class="card-title text-muted">売上高 ({{ $dateRangeLabel }})</h5>
                    <p class="card-text fs-4 fw-bold mb-0">{{ number_format($currentRevenue) }} 円</p>
                    <small class="{{ $revenueChange >= 0 ? 'text-success' : 'text-danger' }}">
                        <i class="bi {{ $revenueChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                        {{ number_format($revenueChange, 1) }}% ({{ $comparisonLabel }})
                    </small>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6 mb-3">
        <div class="card kpi-card">
            <div class="card-body">
                 <div class="kpi-icon bg-info"><i class="bi bi-box-seam"></i></div>
                <div>
                    <h5 class="card-title text-muted">販売数 ({{ $dateRangeLabel }})</h5>
                    <p class="card-text fs-4 fw-bold mb-0">{{ number_format($currentQuantity) }} 個</p>
                     <small class="{{ $quantityChange >= 0 ? 'text-success' : 'text-danger' }}">
                        <i class="bi {{ $quantityChange >= 0 ? 'bi-arrow-up' : 'bi-arrow-down' }}"></i>
                        {{ number_format($quantityChange, 1) }}% ({{ $comparisonLabel }})
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

@include('dashboard.partials.charts')

<div class="row">
    <div class="col-lg-8 mb-4">
        @include('dashboard.partials.detailed-sales-table')
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header text-white bg-warning">
                <i class="bi bi-exclamation-triangle-fill"></i> 在庫アラート
            </div>
            <div class="list-group list-group-flush" style="max-height: 450px; overflow-y: auto;">
                 @forelse ($lowStockItems as $item)
                    <a href="{{ route('inventory.edit', $item) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex w-100 justify-content-between">
                            <h6 class="mb-1">{{ $item->product->name }}</h6>
                            <small class="text-danger fw-bold">残 {{ $item->quantity }}</small>
                        </div>
                        <p class="mb-1 small text-muted">{{ $item->store->name }} (発注点: {{ $item->reorder_point }})</p>
                    </a>
                @empty
                    <div class="list-group-item text-center text-muted p-5">
                        <i class="bi bi-check-circle fs-3"></i>
                        <p class="mt-2">在庫アラートはありません</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="detailsModal" tabindex="-1" aria-labelledby="detailsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailsModalLabel">売上詳細</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="detailsModalContent" class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
{{-- モーダルとカレンダーピッカーを制御するJavaScript --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 詳細表示モーダルの処理
        const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
        const modalTitle = document.getElementById('detailsModalLabel');
        const modalContent = document.getElementById('detailsModalContent');

        document.querySelectorAll('.view-details-btn').forEach(button => {
            button.addEventListener('click', function () {
                const period = this.dataset.period;
                const range = '{{ $currentRange }}';
                let title = '';

                if (range === 'all' || range === 'custom') {
                    const date = new Date(period + '-02'); // タイムゾーン問題を避ける
                    title = date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' }) + ' の売上詳細';
                } else {
                    const date = new Date(period);
                    title = date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric' }) + ' の売上詳細';
                }
                
                modalTitle.textContent = title;
                modalContent.innerHTML = `<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>`;

                fetch(`{{ route('dashboard.salesDetails') }}?period=${period}&range=${range}`)
                    .then(response => response.json())
                    .then(data => {
                        let tableHtml = '<table class="table table-striped"><thead><tr><th>商品名</th><th class="text-end">販売数</th><th class="text-end">売上高</th></tr></thead><tbody>';
                        if (data.length > 0) {
                            data.forEach(item => {
                                tableHtml += `<tr>
                                    <td>${item.name}</td>
                                    <td class="text-end">${item.total_quantity.toLocaleString()} 個</td>
                                    <td class="text-end">${item.total_revenue.toLocaleString()} 円</td>
                                </tr>`;
                            });
                        } else {
                            tableHtml += '<tr><td colspan="3" class="text-center">この期間の売上データはありません。</td></tr>';
                        }
                        tableHtml += '</tbody></table>';
                        modalContent.innerHTML = tableHtml;
                    })
                    .catch(error => {
                        modalContent.innerHTML = '<p class="text-danger text-center">データの読み込みに失敗しました。</p>';
                    });
            });
        });

        // Flatpickr（カレンダーピッカー）の初期化
        flatpickr("#date-range-picker", {
            mode: "range",
            dateFormat: "Y-m-d",
            locale: "ja",
            onChange: function(selectedDates, dateStr, instance) {
                if (selectedDates.length === 2) {
                    const form = document.getElementById('date-range-form');
                    form.querySelectorAll('input[name="start_date"], input[name="end_date"]').forEach(el => el.remove());
                    
                    const startDateInput = document.createElement('input');
                    startDateInput.type = 'hidden';
                    startDateInput.name = 'start_date';
                    startDateInput.value = instance.formatDate(selectedDates[0], "Y-m-d");
                    form.appendChild(startDateInput);

                    const endDateInput = document.createElement('input');
                    endDateInput.type = 'hidden';
                    endDateInput.name = 'end_date';
                    endDateInput.value = instance.formatDate(selectedDates[1], "Y-m-d");
                    form.appendChild(endDateInput);
                }
            }
        });
    });
</script>
@endpush