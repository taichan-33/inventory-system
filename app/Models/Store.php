<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Store extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
    ];

    public function inventories()
    {
        return $this->hasMany(Inventory::class);
    }
}
