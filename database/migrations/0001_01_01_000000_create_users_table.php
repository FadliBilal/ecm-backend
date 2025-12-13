<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->enum('role', ['buyer', 'seller'])->default('buyer');

            // --- KOLOM BARU (LOGIC KOMERCE) ---
            // Kita pakai unsignedBigInteger karena ID Komerce kadang angkanya besar
            $table->unsignedBigInteger('location_id')->nullable()->comment('ID Lokasi dari API Komerce untuk hitung ongkir');
            
            // Kita simpan teks lengkapnya (Contoh: "Tambaksari, Surabaya, Jawa Timur")
            $table->string('location_label')->nullable()->comment('Label lokasi untuk tampilan di profil');
            
            // Alamat detail manual (Jalan, No Rumah, RT/RW)
            $table->text('full_address')->nullable()->comment('Detail alamat jalan/rumah');
            
            // --- END KOLOM BARU ---

            $table->string('phone')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table) {
            $table->string('id')->primary();
            $table->foreignId('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
        Schema::dropIfExists('password_reset_tokens');
        Schema::dropIfExists('sessions');
    }
};