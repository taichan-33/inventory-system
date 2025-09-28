<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Models\Inventory;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // --- 期間設定 ---
        $currentRange = $request->query('range', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');

        $dateRangeLabel = '';
        $comparisonLabel = '';
        $currentPeriod = [];
        $previousPeriod = [];

        if ($startDate && $endDate) {
            $currentRange = 'custom';
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $currentPeriod = [$start, $end];
            
            $durationInDays = $start->diffInDays($end);
            $previousEnd = $start->copy()->subDay();
            $previousStart = $previousEnd->copy()->subDays($durationInDays);
            $previousPeriod = [$previousStart->startOfDay(), $previousEnd->endOfDay()];

            $dateRangeLabel = $start->format('Y/n/j') . ' - ' . $end->format('Y/n/j');
            $comparisonLabel = '前期間比';

        } else {
            switch ($currentRange) {
                case 'today':
                    $currentPeriod = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
                    $previousPeriod = [Carbon::yesterday()->startOfDay(), Carbon::yesterday()->endOfDay()];
                    $dateRangeLabel = '今日';
                    $comparisonLabel = '昨日比';
                    break;
                case 'all':
                    $currentPeriod = [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
                    $previousPeriod = [Carbon::now()->subYear()->startOfYear(), Carbon::now()->subYear()->endOfYear()];
                    $dateRangeLabel = '今年';
                    $comparisonLabel = '前年比';
                    break;
                case 'month':
                default:
                    $currentPeriod = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
                    $previousPeriod = [Carbon::now()->subMonth()->startOfMonth(), Carbon::now()->subMonth()->endOfMonth()];
                    $dateRangeLabel = '今月';
                    $comparisonLabel = '前月比';
                    break;
            }
        }
        
        // --- KPIサマリーと前期間比較 ---
        $kpiQuery = fn($period) => DB::table('sales')
            ->join('products', 'sales.product_id', '=', 'products.id')
            ->whereBetween('sales.sold_at', $period);

        $currentRevenue = $kpiQuery($currentPeriod)->sum(DB::raw('quantity_sold * price'));
        $previousRevenue = $kpiQuery($previousPeriod)->sum(DB::raw('quantity_sold * price'));
        $revenueChange = $previousRevenue > 0 ? (($currentRevenue - $previousRevenue) / $previousRevenue) * 100 : 0;

        $currentQuantity = $kpiQuery($currentPeriod)->sum('quantity_sold');
        $previousQuantity = $kpiQuery($previousPeriod)->sum('quantity_sold');
        $quantityChange = $previousQuantity > 0 ? (($currentQuantity - $previousQuantity) / $previousQuantity) * 100 : 0;
        
        // --- 在庫アラート (変更なし) ---
        $lowStockItems = Inventory::with(['product', 'store'])->whereColumn('quantity', '<=', 'reorder_point')->orderBy('quantity', 'asc')->get();
            
        // --- ドリルダウン用 詳細売上データ ---
        $detailedSales = $this->getDetailedSalesQuery($currentPeriod, $currentRange)->paginate(10);

        // --- グラフ用データ ---
        $comparisonChartData = null;
        if ($currentRange === 'all') { // 今年 vs 昨年 (月別)
            $currentData = $this->getChartDataByMonth($currentPeriod);
            $previousData = $this->getChartDataByMonth($previousPeriod);
            $labels = collect($currentData)->pluck('month')->map(fn($m) => $m . '月');
            $comparisonChartData = [
                'labels' => $labels,
                'datasets' => [
                    ['label' => '今年', 'data' => collect($currentData)->pluck('total_revenue'), 'borderColor' => '#0d6efd', 'backgroundColor' => 'rgba(13, 110, 253, 0.1)'],
                    ['label' => '昨年', 'data' => collect($previousData)->pluck('total_revenue'), 'borderColor' => '#6c757d', 'backgroundColor' => 'rgba(108, 117, 125, 0.1)'],
                ]
            ];
        } elseif (in_array($currentRange, ['month', 'custom', 'today'])) { // 日別比較
            $currentData = $this->getChartDataByDay($currentPeriod);
            $previousData = $this->getChartDataByDay($previousPeriod);
            $labels = collect($currentData)->pluck('day')->map(fn($d) => Carbon::parse($d)->format('j'));
             $comparisonChartData = [
                'labels' => $labels,
                'datasets' => [
                    ['label' => $currentRange === 'month' ? '今月' : '当期間', 'data' => collect($currentData)->pluck('total_revenue'), 'borderColor' => '#0d6efd', 'backgroundColor' => 'rgba(13, 110, 253, 0.1)'],
                    ['label' => $currentRange === 'month' ? '先月' : '前期間', 'data' => collect($previousData)->pluck('total_revenue'), 'borderColor' => '#6c757d', 'backgroundColor' => 'rgba(108, 117, 125, 0.1)'],
                ]
            ];
        }
        
        return view('dashboard.index', compact(
            'currentRevenue', 'revenueChange', 'currentQuantity', 'quantityChange', 'comparisonLabel',
            'lowStockItems', 'detailedSales', 'comparisonChartData',
            'currentRange', 'dateRangeLabel', 'startDate', 'endDate'
        ));
    }
    
    // --- グラフ分析ページ用メソッド ---
    public function analytics(Request $request)
    {
        // 期間設定ロジックを index と共有
        $currentRange = $request->query('range', 'month');
        $startDate = $request->query('start_date');
        $endDate = $request->query('end_date');
        $dateRangeLabel = '';
        $currentPeriod = [];

        if ($startDate && $endDate) {
            $currentRange = 'custom';
            $start = Carbon::parse($startDate)->startOfDay();
            $end = Carbon::parse($endDate)->endOfDay();
            $currentPeriod = [$start, $end];
            $dateRangeLabel = $start->format('Y/n/j') . ' - ' . $end->format('Y/n/j');
        } else {
             switch ($currentRange) {
                case 'today':
                    $currentPeriod = [Carbon::today()->startOfDay(), Carbon::today()->endOfDay()];
                    $dateRangeLabel = '今日';
                    break;
                case 'all':
                    $currentPeriod = [Carbon::now()->startOfYear(), Carbon::now()->endOfYear()];
                    $dateRangeLabel = '今年';
                    break;
                case 'month':
                default:
                    $currentPeriod = [Carbon::now()->startOfMonth(), Carbon::now()->endOfMonth()];
                    $dateRangeLabel = '今月';
                    break;
            }
        }
        $dateFilter = fn($query) => $query->whereBetween('sales.sold_at', $currentPeriod);

        // ... (グラフデータ取得ロジックは前回から変更なし) ...
        $monthlySalesChart = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select(DB::raw("DATE_FORMAT(sold_at, '%Y-%m') as month"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->where('sold_at', '>=', Carbon::now()->subMonths(11)->startOfMonth())->groupBy('month')->orderBy('month', 'asc')->get();
        $storeShareChart = DB::table('sales')->join('stores', 'sales.store_id', '=', 'stores.id')->join('products', 'sales.product_id', '=', 'products.id')->select('stores.name', DB::raw('SUM(quantity_sold * price) as total_revenue'))->whereBetween('sales.sold_at', $currentPeriod)->groupBy('stores.name')->orderByDesc('total_revenue')->get();
        $productSalesChart = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select('products.name', DB::raw('SUM(quantity_sold * price) as total_revenue'))->whereBetween('sales.sold_at', $currentPeriod)->groupBy('products.name')->orderByDesc('total_revenue')->limit(5)->get();
        $salesByDayOfWeek = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select(DB::raw("DAYOFWEEK(sold_at) as day_of_week"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->whereBetween('sales.sold_at', $currentPeriod)->groupBy('day_of_week')->orderBy('day_of_week', 'asc')->get()->keyBy('day_of_week');
        $dayOfWeekData = []; $days = ['日', '月', '火', '水', '木', '金', '土'];
        for ($i = 1; $i <= 7; $i++) { $dayOfWeekData['labels'][] = $days[$i - 1]; $dayOfWeekData['data'][] = $salesByDayOfWeek->has($i) ? $salesByDayOfWeek[$i]->total_revenue : 0; }

        return view('dashboard.analytics', compact('monthlySalesChart', 'storeShareChart', 'productSalesChart', 'dayOfWeekData', 'currentRange', 'dateRangeLabel', 'startDate', 'endDate'));
    }

    // --- AJAXで詳細データを返すメソッド (カスタム期間対応) ---
    public function getSalesDetailsForPeriod(Request $request)
    {
        $period = $request->query('period');
        $range = $request->query('range');
        $query = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select('products.name', DB::raw('SUM(quantity_sold) as total_quantity'), DB::raw('SUM(quantity_sold * price) as total_revenue'))->groupBy('products.name')->orderByDesc('total_revenue');
        
        // 'custom'の場合も日別詳細と同じ扱いにする
        if ($range === 'all' && strlen($period) == 7) {
            $query->whereYear('sold_at', substr($period, 0, 4))->whereMonth('sold_at', substr($period, 5, 2));
        } else {
            $query->whereDate('sold_at', $period);
        }
        return response()->json($query->get());
    }

    // --- データ取得ヘルパーメソッド ---
    private function getDetailedSalesQuery($period, $range)
    {
        $query = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id');
        if ($range === 'all') { // 月別
            $query->select(DB::raw("DATE_FORMAT(sold_at, '%Y-%m') as period"), DB::raw("SUM(quantity_sold) as total_quantity"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->whereBetween('sold_at', $period);
        } else { // 日別
            $query->select(DB::raw("DATE(sold_at) as period"), DB::raw("SUM(quantity_sold) as total_quantity"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->whereBetween('sold_at', $period);
        }
        return $query->groupBy('period')->orderBy('period', 'desc');
    }

    private function getChartDataByMonth($period) {
        $data = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select(DB::raw("MONTH(sold_at) as month"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->whereBetween('sold_at', $period)->groupBy('month')->orderBy('month', 'asc')->get()->keyBy('month');
        $result = []; for ($i = 1; $i <= 12; $i++) { $result[] = ['month' => $i, 'total_revenue' => $data->has($i) ? $data[$i]->total_revenue : 0]; } return $result;
    }

    private function getChartDataByDay($period) {
        $data = DB::table('sales')->join('products', 'sales.product_id', '=', 'products.id')->select(DB::raw("DATE(sold_at) as day"), DB::raw("SUM(quantity_sold * price) as total_revenue"))->whereBetween('sold_at', $period)->groupBy('day')->orderBy('day', 'asc')->get()->keyBy('day');
        $result = []; $date = $period[0]->copy();
        while ($date->lte($period[1])) {
            $dayStr = $date->toDateString();
            $result[] = ['day' => $dayStr, 'total_revenue' => $data->has($dayStr) ? $data[$dayStr]->total_revenue : 0];
            $date->addDay();
        }
        return $result;
    }
}