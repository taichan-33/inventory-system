<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id',
        'quantity',
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function pendingPurchaseOrder()
    {
        return $this->hasOne(PurchaseOrder::class, 'product_id', 'product_id')
                    ->whereColumn('purchase_orders.store_id', 'inventories.store_id')
                    ->where('status', '!=', 'completed');
    }
}