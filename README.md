# 🛒 Tukuo – Backend API (Laravel)

Backend API untuk aplikasi **Tukuo**, sebuah **E-Commerce Marketplace (multi-seller)** berbasis mobile. Backend ini berperan sebagai **REST API Server** yang menangani autentikasi, produk, keranjang, checkout, ongkir otomatis, dan pembayaran digital.

---

## 🚀 Gambaran Umum Aplikasi

Tukuo **bukan toko tunggal**, melainkan marketplace dengan konsep:

* Produk dikirim dari **lokasi seller (origin dinamis)**
* Produk diterima di **lokasi buyer (destination dari profil user)**
* Ongkir dihitung otomatis berdasarkan data real
* Pembayaran terintegrasi dengan payment gateway

Backend ini dirancang agar mudah dikonsumsi oleh **Mobile App Flutter**.

---

## 🧱 Tech Stack

* **Framework** : Laravel 10 / 11
* **Database** : MySQL
* **Authentication** : Laravel Sanctum (Bearer Token)
* **API Type** : RESTful API
* **Shipping API** : RajaOngkir
* **Payment Gateway** : Xendit

---

## 📂 Struktur Folder Penting

```
app/
 ├── Http/
 │   ├── Controllers/
 │   │   ├── AuthController.php
 │   │   ├── ProductController.php
 │   │   ├── CartController.php
 │   │   ├── CheckoutController.php
 │   │   └── PaymentController.php
 │   └── Middleware/
 ├── Models/
 ├── Services/
 │   ├── RajaOngkirService.php
 │   └── XenditService.php
routes/
 └── api.php
```

---

## 🛍️ Fitur Utama Backend

### 1️⃣ Authentication

* Register user
* Login user
* Logout user
* Validasi token otomatis

---

### 2️⃣ Manajemen Produk

* Menampilkan daftar produk
* Menampilkan detail produk
* Menyimpan informasi:

  * Harga
  * Stok
  * Berat produk
  * Lokasi seller (city_id)

---

### 3️⃣ Keranjang (Cart)

* Tambah produk ke keranjang
* Update quantity produk
* Hapus produk dari keranjang
* Data cart tersimpan di database

---

### 4️⃣ Checkout (Fitur Inti)

Alur checkout di backend:

1. Validasi data user

   * Alamat
   * Nomor HP

2. Ambil data pengiriman

   * **Origin** → lokasi seller
   * **Destination** → lokasi user

3. Hitung berat total

   * Berat produk × quantity

4. Hitung ongkir otomatis

   * Request ke RajaOngkir
   * Kurir: JNE, POS, TIKI

5. Buat order & transaksi

---

### 5️⃣ Pembayaran

* Terintegrasi dengan **Xendit**
* Mendukung:

  * Virtual Account
  * QRIS
* Backend akan:

  * Membuat invoice
  * Menghasilkan `payment_url`
  * Menyimpan status transaksi

Status pembayaran:

* `pending`
* `paid`
* `failed`

---

## 🌍 Integrasi RajaOngkir

Digunakan untuk menghitung ongkir real-time berdasarkan:

* Kota asal (seller)
* Kota tujuan (buyer)
* Total berat
* Kurir

API Key RajaOngkir disimpan di `.env`

---

## 💳 Integrasi Xendit

Digunakan sebagai payment gateway:

* Generate invoice pembayaran
* Redirect pembayaran via `payment_url`
* Mendukung callback/webhook (opsional)

API Key Xendit disimpan di `.env`

---

## ⚙️ Cara Setup Project

### 1️⃣ Clone Repository

```
git clone https://github.com/FadliBilal/ecm-backend.git
cd tukuo-backend
```

### 2️⃣ Install Dependency

```
composer install
```

### 3️⃣ Setup Environment

```
cp .env.example .env
php artisan key:generate
```

Isi konfigurasi penting:

* Database MySQL
* RajaOngkir API Key
* Xendit API Key

---

### 4️⃣ Migration Database

```
php artisan migrate
```

---

### 5️⃣ Jalankan Server

```
php artisan serve
```

Backend akan berjalan di:

```
http://127.0.0.1:8000
```

---

## 📌 Catatan Penting

* Backend **tidak memiliki UI**
* Digunakan khusus oleh aplikasi Flutter
* Pastikan API Key valid agar ongkir & pembayaran berjalan

---

## 👨‍💻 Penutup

Backend **Tukuo** dirancang modular, scalable, dan mudah dipelajari untuk kebutuhan pembelajaran maupun pengembangan lanjutan aplikasi marketplace.

Silakan kembangkan dan sesuaikan sesuai kebutuhan 🚀
