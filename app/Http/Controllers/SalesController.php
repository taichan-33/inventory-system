<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Sale;
use App\Models\Inventory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SalesController extends Controller
{
    public function store(Request $request)
    {
        // バリデーション
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'store_id' => 'required|exists:stores,id',
            'quantity_sold' => 'required|integer|min:1',
        ]);

        try {
            DB::transaction(function() use ($validated) {
                // 在庫レコードを判定
                $inventory = Inventory::where('product_id', $validated['product_id'])->where('store_id', $validated['store_id'])->firstOrFail(); // 在庫が存在しない場合は例外をスロー

            // 在庫が足りるか確認
            if ($inventory->quantity < $validated['quantity_sold']) {
                // 在庫不足の場合、例外をスロー
                throw ValidationException::withMessages([
                    'quantity_sold' => '在庫が不足しています。',
                ]);
        }
            // 在庫を減らす
            $inventory->decrement('quantity', $validated['quantity_sold']);

            // 売上レコードを作成
            Sale::create([
                'product_id' => $validated['product_id'],
                'store_id' => $validated['store_id'],
                'quantity_sold' => $validated['quantity_sold'],
                'sold_at' => now(),
            ]);
        });
        } catch (ValidationException $e) {
            // 在庫不足の場合エラーメッセージを渡し前のページに戻る
            return back()->withErrors($e->errors());
        }
        // 成功したら在庫一覧にリダイレクト
        return redirect()->route('inventory.index')->with('success', '売上が登録されました。'); 
    }
}
