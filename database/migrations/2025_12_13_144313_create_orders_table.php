<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            $table->string('order_number')->unique(); 
            
            // Info Pengiriman
            $table->string('shipping_service');
            $table->integer('shipping_cost');
            $table->string('courier');
            
            // Info Pembayaran
            $table->decimal('total_price', 15, 2); 
            $table->string('status')->default('PENDING'); 
            $table->string('payment_method')->default('xendit');
            
            // Snapshot Alamat (Penting!)
            $table->text('address')->nullable();
            $table->string('phone')->nullable();
            $table->string('postal_code')->nullable();
            $table->text('notes')->nullable();

            // Xendit
            $table->string('xendit_invoice_id')->nullable();
            $table->string('xendit_invoice_url')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            
            // Relasi ke tabel Orders (Kalau order dihapus, item ikut terhapus)
            $table->foreignId('order_id')->constrained('orders')->onDelete('cascade');
            
            // Relasi ke tabel Products
            $table->foreignId('product_id')->constrained('products');
            
            $table->integer('quantity');
            $table->decimal('price', 15, 2); // Simpan harga saat beli (Snapshot Price)
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items'); 
        Schema::dropIfExists('orders');      
    }
};
