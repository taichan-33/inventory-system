<div class="card h-100">
    <div class="card-header">
        @if($currentRange === 'all')
            月別売上レポート
        @elseif($currentRange === 'month')
            日別売上レポート
        @else
            詳細レポート
        @endif
    </div>
    <div class="card-body d-flex flex-column">
        @if($detailedSales && $detailedSales->total() > 0)
            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>期間</th>
                            <th class="text-end">販売数</th>
                            <th class="text-end">売上高</th>
                            <th class="text-end">詳細</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($detailedSales as $sale)
                            <tr>
                                <td>
                                    @if($currentRange === 'all')
                                        {{ \Carbon\Carbon::parse($sale->period . '-01')->format('Y年n月') }}
                                    @else
                                        {{ \Carbon\Carbon::parse($sale->period)->format('n月j日') }}
                                    @endif
                                </td>
                                <td class="text-end">{{ number_format($sale->total_quantity) }} 個</td>
                                <td class="text-end fw-bold">{{ number_format($sale->total_revenue) }} 円</td>
                                <td class="text-end">
                                    <button class="btn btn-outline-primary btn-sm view-details-btn" 
                                            data-bs-toggle="modal" 
                                            data-bs-target="#detailsModal"
                                            data-period="{{ $sale->period }}">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="mt-auto d-flex justify-content-center pt-3">
                {{ $detailedSales->appends(request()->query())->links() }}
            </div>
        @else
            <div class="text-center text-muted p-5 m-auto">
                <i class="bi bi-bar-chart-line fs-3"></i>
                <p class="mt-2">
                    @if($currentRange === 'today')
                        日別・月別レポートは「今月」または「今年」を選択してください。
                    @else
                        表示できる詳細データがありません。
                    @endif
                </p>
            </div>
        @endif
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const detailsModal = new bootstrap.Modal(document.getElementById('detailsModal'));
    const modalTitle = document.getElementById('detailsModalLabel');
    const modalContent = document.getElementById('detailsModalContent');

    document.querySelectorAll('.view-details-btn').forEach(button => {
        button.addEventListener('click', function () {
            const period = this.dataset.period;
            const range = '{{ $currentRange }}';
            let title = '';

            if (range === 'all') {
                const date = new Date(period + '-02'); // タイムゾーン問題を避けるため2日を指定
                title = date.toLocaleDateString('ja-JP', { year: 'numeric', month: 'long' }) + ' の売上詳細';
            } else {
                const date = new Date(period);
                title = date.toLocaleDateString('ja-JP', { month: 'long', day: 'numeric' }) + ' の売上詳細';
            }
            
            modalTitle.textContent = title;
            modalContent.innerHTML = '<div class="text-center p-5"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';

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
                    modalContent.innerHTML = '<p class="text-danger">データの読み込みに失敗しました。</p>';
                });
        });
    });
});
</script>
@endpush