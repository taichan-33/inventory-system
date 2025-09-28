<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'store_id', // 追加
        'quantity',
        'status',
        'arrival_date', // 追加
    ];
}