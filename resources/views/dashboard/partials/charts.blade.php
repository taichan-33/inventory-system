<div class="row mb-4">
    <div class="col-lg-12 mb-4">
        <div class="card h-100">
            <div class="card-header">期間売上比較</div>
            <div class="card-body">
                @if($comparisonChartData)
                    <canvas id="comparisonBarChart" height="300"></canvas>
                @else
                    <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                        <i class="bi bi-bar-chart-line fs-3 me-2"></i> 「今月」または「今年」を選択すると比較グラフが表示されます。
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    (function() {
        'use strict';
        const comparisonCtx = document.getElementById('comparisonBarChart');
        const chartData = @json($comparisonChartData);
        if (comparisonCtx && chartData) {
            new Chart(comparisonCtx, {
                type: 'bar',
                data: {
                    labels: chartData.labels,
                    datasets: chartData.datasets.map(dataset => ({
                        ...dataset,
                        tension: 0.3,
                        fill: true
                    }))
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true },
                        x: { grid: { display: false } }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index',
                    },
                }
            });
        }
    })();
</script>
@endpush