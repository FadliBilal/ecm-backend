<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'order_number',      
        'status',
        'total_price',
        'shipping_cost',
        'shipping_service',
        'courier',
        'payment_method',
        'address',
        'phone',
        'postal_code',
        'notes',
        'xendit_invoice_id',
        'xendit_invoice_url'
    ];

    // Casting tipe data (Opsional, tapi bagus untuk memastikan angka tidak jadi string)
    protected $casts = [
        'total_price' => 'decimal:2',
        'shipping_cost' => 'integer',
        'user_id' => 'integer',
    ];

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