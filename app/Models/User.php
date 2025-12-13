<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'location_id',     
        'location_label',  
        'full_address',    
        'phone',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'location_id' => 'integer',
        ];
    }

    // Relasi: User (Seller) punya banyak Produk
    public function products()
    {
        return $this->hasMany(Product::class, 'seller_id');
    }

    // Relasi: User (Buyer) punya 1 Keranjang aktif
    public function cart()
    {
        return $this->hasOne(Cart::class);
    }

    // Relasi: User (Buyer) punya banyak Order history
    public function orders()
    {
        return $this->hasMany(Order::class);
    }
}
