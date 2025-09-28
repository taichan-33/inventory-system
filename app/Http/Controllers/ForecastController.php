<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\PurchaseOrder;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class ForecastController extends Controller
{
    public function index()
    {
        return view('forecast.index');
    }

    public function runBatchForecast(Request $request)
    {
        // 1. 発注済み（未完了）の注文を取得し、検索しやすいように整形
        $pendingOrders = PurchaseOrder::where('status', '!=', 'completed')
            ->get()
            ->keyBy(fn ($item) => $item->product_id . '-' . $item->store_id);
        
        // 2. 発注点を下回っている在庫を取得
        $lowStockInventories = Inventory::with(['product', 'store'])
            ->whereColumn('quantity', '<=', 'reorder_point')
            ->get();

        if ($lowStockInventories->isEmpty()) {
            $totalInventoryCount = Inventory::count();
            $message = "現在、発注点を下回っている商品がないため、予測は実行されませんでした。(全{$totalInventoryCount}件の在庫は健全な状態です)";
            return back()->with('info', $message);
        }

        $salesDataString = "";
        foreach ($lowStockInventories as $inventory) {
            $key = $inventory->product_id . '-' . $inventory->store_id;
            $pendingQty = $pendingOrders->has($key) ? $pendingOrders[$key]->quantity : 0;
            
            $sales = Sale::where('product_id', $inventory->product_id)
                ->where('store_id', $inventory->store_id)
                ->orderBy('sold_at', 'asc')
                ->limit(90)
                ->get(['sold_at', 'quantity_sold']);

            // 3. AIに渡す情報に「発注済み未入荷数」を追加
            $salesDataString .= "--- product_id: {$inventory->product_id}, store_id: {$inventory->store_id}, 商品名: {$inventory->product->name}, 店舗: {$inventory->store_name}, 現在在庫: {$inventory->quantity}, 発注済み未入荷数: {$pendingQty} ---\n";
            if ($sales->isEmpty()) {
                $salesDataString .= "売上データなし\n";
            } else {
                foreach ($sales as $sale) {
                    $salesDataString .= $sale->sold_at->format('Y-m-d') . ", " . $sale->quantity_sold . "\n";
                }
            }
            $salesDataString .= "\n";
        }

        // 4. AIへの指示（プロンプト）を更新
        $prompt = "
あなたは優秀なサプライチェーン・アナリストです。
以下の複数商品のデータに基づき、需要予測と発注推奨を行ってください。

# 前提条件
- 商品の補充にかかる日数（リードタイム）: 3日
- 安全在庫は「リードタイム中の平均販売数 + 需要変動を吸収する量」で計算してください。
- 発注推奨数は「(7日間の予測販売数 + 安全在庫) - 現在の在庫数 - 発注済み未入荷数」を基本とし、マイナスになる場合は0としてください。

# データ
{$salesDataString}

# 指示
上記の各商品を分析し、結果をJSON形式で出力してください。
- ルート要素は `forecasts` というキーを持つオブジェクトとします。
- `forecasts` の値は、各商品の予測結果オブジェクトを含む配列とします。
- 各商品オブジェクトには、以下のキーを含めてください。
  - `product_id`: 商品ID (integer)
  - `store_id`: 店舗ID (integer)
  - `product_name`: 商品名 (string)
  - `store_name`: 店舗名 (string)
  - `current_stock`: 現在の在庫数 (integer)
  - `predicted_sales_7d`: 7日間の予測販売数 (integer)
  - `recommended_order`: 推奨発注数 (integer)
  - `reasoning`: なぜその数値を予測し、その発注数が妥当と判断したのかの簡潔な根拠 (string)

JSON以外の説明文は絶対に含めないでください。
";
        
        try {
            $response = Http::timeout(180)
                ->withToken(config('openai.api_key'))
                ->post('https://api.openai.com/v1/responses', [
                    'model' => 'o3-pro-2025-06-10',
                    'input' => [['role' => 'user', 'content' => $prompt]],
                ]);

            if ($response->failed()) {
                Log::error('AI API request failed: ' . $response->body());
                return back()->withErrors(['api_error' => 'AI予測の実行中にエラーが発生しました。']);
            }
            
            $responseData = $response->json();
            $jsonString = $responseData['output'][1]['content'][0]['text'] ?? null;
            
            if (!$jsonString) { return back()->withErrors(['api_error' => 'AIからの結果を解析できませんでした。']); }

            $decodedResult = json_decode($jsonString, true);
            
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedResult['forecasts'])) {
                Log::error('Failed to decode AI JSON response or invalid format: ' . $jsonString);
                return back()->withErrors(['api_error' => 'AIが予期せぬ形式で応答しました。']);
            }
            
            $result = $decodedResult['forecasts'];

            // 5. ビューに渡す結果に、発注済み情報を追加
            foreach ($result as &$item) {
                $key = ($item['product_id'] ?? '') . '-' . ($item['store_id'] ?? '');
                if ($pendingOrders->has($key)) {
                    $item['pending_order_quantity'] = $pendingOrders[$key]->quantity;
                    $item['arrival_date'] = Carbon::parse($pendingOrders[$key]->arrival_date)->format('n/j');
                } else {
                    $item['pending_order_quantity'] = 0;
                    $item['arrival_date'] = null;
                }
            }

        } catch (\Exception $e) {
            Log::error('AI API connection error: ' . $e->getMessage());
            return back()->withErrors(['api_error' => 'AIサービスに接続できませんでした。']);
        }

        return view('forecast.index', compact('result'));
    }
}

