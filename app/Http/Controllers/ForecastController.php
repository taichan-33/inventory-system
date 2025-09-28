<?php

namespace App\Http\Controllers;

use App\Models\Inventory;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ForecastController extends Controller
{
    public function index()
    {
        return view('forecast.index');
    }

    public function runBatchForecast(Request $request)
    {
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
            $sales = Sale::where('product_id', $inventory->product_id)
                ->where('store_id', $inventory->store_id)
                ->orderBy('sold_at', 'asc')
                ->limit(90) // 直近90日分のデータに制限
                ->get(['sold_at', 'quantity_sold']);

            $salesDataString .= "--- 商品ID: {$inventory->id}, 商品名: {$inventory->product->name}, 店舗: {$inventory->store->name}, 現在在庫: {$inventory->quantity} ---\n";
            if ($sales->isEmpty()) {
                $salesDataString .= "売上データなし\n";
            } else {
                foreach ($sales as $sale) {
                    $salesDataString .= $sale->sold_at->format('Y-m-d') . ", " . $sale->quantity_sold . "\n";
                }
            }
            $salesDataString .= "\n";
        }

        // --- プロンプトをJSON出力要求に最適化 ---
        $prompt = "
あなたは優秀なサプライチェーン・アナリストです。
以下の複数商品のデータに基づき、需要予測と発注推奨を行ってください。

# 前提条件
- 商品の補充にかかる日数（リードタイム）: 3日
- 安全在庫は「リードタイム中の平均販売数 + 需要変動を吸収する量」で計算してください。
- 発注推奨数は「(7日間の予測販売数 + 安全在庫) - 現在の在庫数」を基本とし、マイナスになる場合は0としてください。

# データ
{$salesDataString}

# 指示
上記の各商品を分析し、結果をJSON形式で出力してください。
- ルート要素は `forecasts` というキーを持つオブジェクトとします。
- `forecasts` の値は、各商品の予測結果オブジェクトを含む配列とします。
- 各商品オブジェクトには、以下のキーを含めてください。
  - `product_name`: 商品名 (string)
  - `store_name`: 店舗名 (string)
  - `current_stock`: 現在の在庫数 (integer)
  - `predicted_sales_7d`: 7日間の予測販売数 (integer)
  - `recommended_order`: 推奨発注数 (integer)
  - `reasoning`: なぜその数値を予測し、その発注数が妥当と判断したのかの簡潔な根拠 (string)

JSON以外の説明文は絶対に含めないでください。
";
        
        try {
            // ユーザー指定のAPIエンドポイントとモデルを使用
            $response = Http::timeout(180)
                ->withToken(config('openai.api_key')) // .env等での設定を推奨
                ->post('https://api.openai.com/v1/responses', [
                    'model' => 'o3-pro-2025-06-10',
                    'input' => [
                        ['role' => 'user', 'content' => $prompt],
                    ],
                ]);

            if ($response->failed()) {
                Log::error('AI API request failed: ' . $response->body());
                return back()->withErrors(['api_error' => 'AI予測の実行中にエラーが発生しました。']);
            }
            
            $responseData = $response->json();
            $jsonString = $responseData['output'][1]['content'][0]['text'] ?? null;
            
            if (!$jsonString) {
                return back()->withErrors(['api_error' => 'AIからの結果を解析できませんでした。']);
            }

            // JSON文字列をデコード
            $decodedResult = json_decode($jsonString, true);
            
            // JSONデコード失敗、または期待する形式でない場合のエラーハンドリング
            if (json_last_error() !== JSON_ERROR_NONE || !isset($decodedResult['forecasts'])) {
                Log::error('Failed to decode AI JSON response or invalid format: ' . $jsonString);
                return back()->withErrors(['api_error' => 'AIが予期せぬ形式で応答しました。']);
            }
            
            $result = $decodedResult['forecasts'];

        } catch (\Exception $e) {
            Log::error('AI API connection error: ' . $e->getMessage());
            return back()->withErrors(['api_error' => 'AIサービスに接続できませんでした。']);
        }

        return view('forecast.index', compact('result'));
    }
}