<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Models\Inventory;
use App\Models\PurchaseOrder;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class UpdateStockAfterLeadTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $purchaseOrder;

    public function __construct(PurchaseOrder $purchaseOrder)
    {
        $this->purchaseOrder = $purchaseOrder;
    }

    public function handle(): void
    {
        // データベースのトランザクションを利用して、処理の安全性を高める
        DB::transaction(function () {
            // 対応する在庫レコードをロックして取得
            $inventory = Inventory::where('product_id', $this->purchaseOrder->product_id)
                ->where('store_id', $this->purchaseOrder->store_id)
                ->lockForUpdate()
                ->first();

            if ($inventory) {
                // 在庫数を増やす
                $inventory->increment('quantity', $this->purchaseOrder->quantity);
                
                // 発注ステータスを 'completed' に更新
                $this->purchaseOrder->status = 'completed';
                $this->purchaseOrder->save();

                Log::info("Stock updated for Purchase Order ID: {$this->purchaseOrder->id}");
            } else {
                // 在庫レコードが見つからない場合はエラーを記録
                Log::error("Inventory not found for Purchase Order ID: {$this->purchaseOrder->id}");
                $this->purchaseOrder->status = 'failed';
                $this->purchaseOrder->save();
            }
        });
    }
}