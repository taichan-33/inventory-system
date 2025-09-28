<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PurchaseOrder;
use App\Jobs\UpdateStockAfterLeadTime;
use Carbon\Carbon;

class PurchaseOrderController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'orders' => 'required|array',
            'orders.*.product_id' => 'required|exists:products,id',
            'orders.*.store_id'   => 'required|exists:stores,id',
            'orders.*.quantity'   => 'required|integer|min:1',
        ]);

        $orderedCount = 0;
        foreach ($validated['orders'] as $orderData) {
            // 既に発注済みでないか最終チェック（念のため）
            $isPending = PurchaseOrder::where('product_id', $orderData['product_id'])
                ->where('store_id', $orderData['store_id'])
                ->where('status', '!=', 'completed')
                ->exists();

            if (!$isPending) {
                $purchaseOrder = PurchaseOrder::create([
                    'product_id' => $orderData['product_id'],
                    'store_id'   => $orderData['store_id'],
                    'quantity'   => $orderData['quantity'],
                    'status'     => 'shipping',
                    'arrival_date' => Carbon::now()->addDays(3),
                ]);

                UpdateStockAfterLeadTime::dispatch($purchaseOrder)->delay(Carbon::now()->addDays(3));
                $orderedCount++;
            }
        }
        
        if ($orderedCount > 0) {
            $message = $orderedCount . "件の商品を発注しました。(3日後に入荷予定)";
            return back()->with('success', $message);
        }

        return back()->with('info', '発注する商品が選択されなかったか、すでに入荷待ちです。');
    }
}
