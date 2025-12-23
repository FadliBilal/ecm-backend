<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductController extends Controller
{
    /**
     * @OA\Get(
     * path="/products",
     * tags={"Products (Public)"},
     * summary="Lihat Semua Produk (Untuk Home Buyer)",
     * description="Menampilkan semua produk terbaru beserta data Sellernya.",
     * @OA\Response(
     * response=200,
     * description="List Semua Produk",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="price", type="integer"),
     * @OA\Property(property="image", type="string", description="Path gambar (tambahkan base_url/storage/ di frontend)"),
     * @OA\Property(property="seller", type="object", @OA\Property(property="name", type="string"))
     * ))
     * )
     * )
     * )
     */
    public function index()
    {
        $products = Product::with('seller:id,name,location_label')->latest()->get();
        return response()->json(['data' => $products]);
    }

    /**
     * @OA\Post(
     * path="/products",
     * tags={"Products (Seller)"},
     * summary="Tambah Produk Baru",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="name", type="string", example="Laptop Gaming"),
     * @OA\Property(property="price", type="integer", example=15000000),
     * @OA\Property(property="weight", type="integer", example=2000, description="Berat dalam Gram (Penting buat Ongkir)"),
     * @OA\Property(property="stock", type="integer", example=10),
     * @OA\Property(property="description", type="string", example="Laptop spek dewa"),
     * @OA\Property(property="image", type="string", format="binary")
     * )
     * )
     * ),
     * @OA\Response(response=201, description="Produk Berhasil Dibuat")
     * )
     */
    public function store(Request $request)
    {
        if ($request->user()->role !== 'seller') {
            return response()->json(['message' => 'Hanya seller yang boleh tambah produk'], 403);
        }

        $validated = $request->validate([
            'name' => 'required|string',
            'price' => 'required|numeric',
            'weight' => 'required|integer', 
            'stock' => 'required|integer',
            'description' => 'nullable|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ]);

        $imagePath = null;
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('products', 'public');
        }

        $product = Product::create([
            'seller_id' => $request->user()->id,
            'name' => $validated['name'],
            'price' => $validated['price'],
            'weight' => $validated['weight'],
            'stock' => $validated['stock'],
            'description' => $validated['description'],
            'image' => $imagePath,
        ]);

        return response()->json(['message' => 'Produk berhasil dibuat', 'data' => $product], 201);
    }

    /**
     * @OA\Get(
     * path="/seller/products",
     * tags={"Products (Seller)"},
     * summary="Lihat Produk Saya (Manage Produk)",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="List Produk milik Seller yang sedang login")
     * )
     */
    public function myProducts(Request $request)
    {
        if ($request->user()->role !== 'seller') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $products = Product::where('seller_id', $request->user()->id)->latest()->get();
        return response()->json(['data' => $products]);
    }

    /**
     * @OA\Post(
     * path="/products/{id}",
     * tags={"Products (Seller)"},
     * summary="Update Produk (Termasuk Gambar)",
     * description="Gunakan method POST dengan body _method=PUT agar bisa upload file di Laravel",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * @OA\MediaType(
     * mediaType="multipart/form-data",
     * @OA\Schema(
     * @OA\Property(property="_method", type="string", example="PUT", description="Wajib diisi PUT"),
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="price", type="integer"),
     * @OA\Property(property="weight", type="integer"),
     * @OA\Property(property="stock", type="integer"),
     * @OA\Property(property="description", type="string"),
     * @OA\Property(property="image", type="string", format="binary")
     * )
     * )
     * ),
     * @OA\Response(response=200, description="Produk Berhasil Diupdate")
     * )
     */
    public function update(Request $request, $id)
    {
        // 1. Cari Produk berdasarkan ID
        $product = Product::find($id);

        // 2. Cek apakah produk ada?
        if (!$product) {
            return response()->json(['message' => 'Produk tidak ditemukan'], 404);
        }

        // 3. Cek Kepemilikan (PENTING!)
        // Jangan sampai Seller A mengedit produk Seller B
        if ($product->seller_id !== $request->user()->id) {
            return response()->json(['message' => 'Anda tidak berhak mengedit produk ini'], 403);
        }

        // 4. Validasi Input (Hanya validasi yg dikirim saja)
        $request->validate([
            'name'        => 'nullable|string',
            'description' => 'nullable|string',
            'price'       => 'nullable|integer',
            'stock'       => 'nullable|integer|min:0', // <--- INI BUAT RESTOCK
            'weight'      => 'nullable|integer',
            // 'image'    => 'nullable|image' (Nanti dulu biar simpel)
        ]);

        // 5. Update Data
        // Kita gunakan logic: Kalau ada input baru, update. Kalau tidak, pakai data lama.
        $product->update([
            'name'        => $request->name ?? $product->name,
            'description' => $request->description ?? $product->description,
            'price'       => $request->price ?? $product->price,
            'stock'       => $request->stock ?? $product->stock, 
            'weight'      => $request->weight ?? $product->weight,
        ]);

        return response()->json([
            'message' => 'Produk berhasil diupdate',
            'data'    => $product
        ]);
    }

    /**
     * @OA\Delete(
     * path="/products/{id}",
     * tags={"Products (Seller)"},
     * summary="Hapus Produk",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Produk Berhasil Dihapus")
     * )
     */
    public function destroy(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        if ($request->user()->id !== $product->seller_id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        if ($product->image && Storage::disk('public')->exists($product->image)) {
            Storage::disk('public')->delete($product->image);
        }

        $product->delete();

        return response()->json(['message' => 'Produk dihapus']);
    }
}