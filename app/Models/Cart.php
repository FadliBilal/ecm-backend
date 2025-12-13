<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Cart extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi: Keranjang milik 1 User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Keranjang punya banyak Item
    public function items()
    {
        return $this->hasMany(CartItem::class);
    }
}