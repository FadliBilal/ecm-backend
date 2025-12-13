<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class OrderItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi: OrderItem bagian dari Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Relasi: OrderItem merujuk ke Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}