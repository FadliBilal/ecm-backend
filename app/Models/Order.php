<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Order extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    // Relasi: Order milik User (Buyer)
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Relasi: Order punya banyak detail Item
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }
}