<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'price' => 'decimal:2', // Biar keluar angka desimal, bukan string
        'weight' => 'integer',
        'stock' => 'integer',
    ];

    // Relasi: Produk dimiliki oleh Seller (User)
    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_id');
    }
    
    // Relasi ke CartItem (opsional, jika ingin cek produk ini ada di keranjang siapa saja)
    public function cartItems()
    {
        return $this->hasMany(CartItem::class);
    }
}