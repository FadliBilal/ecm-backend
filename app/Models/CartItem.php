<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CartItem extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi: Item milik Cart
    public function cart()
    {
        return $this->belongsTo(Cart::class);
    }

    // Relasi: Item adalah sebuah Product
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}