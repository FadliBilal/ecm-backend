<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * @OA\Get(
     * path="/cart",
     * tags={"Cart (Buyer)"},
     * summary="Lihat Keranjang Belanja",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Detail Keranjang",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="object",
     * @OA\Property(property="items", type="array", @OA\Items(
     * @OA\Property(property="id", type="integer", description="ID Cart Item"),
     * @OA\Property(property="quantity", type="integer"),
     * @OA\Property(property="product", type="object",
     * @OA\Property(property="id", type="integer"),
     * @OA\Property(property="name", type="string"),
     * @OA\Property(property="price", type="integer"),
     * @OA\Property(property="stock", type="integer", description="Sisa stok saat ini"),
     * @OA\Property(property="image", type="string")
     * )
     * ))
     * )
     * )
     * )
     * )
     */
    public function index(Request $request)
    {
        $cart = Cart::where('user_id', $request->user()->id)->with('items.product')->first();
        return response()->json(['data' => $cart]);
    }

    /**
     * @OA\Post(
     * path="/cart",
     * tags={"Cart (Buyer)"},
     * summary="Tambah Barang ke Keranjang",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="product_id", type="integer", example=1),
     * @OA\Property(property="quantity", type="integer", example=1)
     * )
     * ),
     * @OA\Response(response=200, description="Berhasil masuk keranjang")
     * )
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'integer|min:1'
        ]);

        $user = $request->user();
        // Ambil atau buat keranjang
        $cart = Cart::firstOrCreate(['user_id' => $user->id]);

        // Cek stok produk sebelum ditambahkan
        $product = Product::find($request->product_id);
        if($product->stock < $request->quantity) {
             return response()->json(['message' => 'Stok tidak cukup'], 400);
        }

        // Cek apakah produk sudah ada di cart, kalau ada update qty
        $item = CartItem::where('cart_id', $cart->id)
                        ->where('product_id', $request->product_id)
                        ->first();

        if ($item) {
            $item->quantity += $request->quantity;
            $item->save();
        } else {
            CartItem::create([
                'cart_id' => $cart->id,
                'product_id' => $request->product_id,
                'quantity' => $request->quantity
            ]);
        }

        return response()->json(['message' => 'Berhasil masuk keranjang']);
    }

    /**
     * @OA\Put(
     * path="/cart/item/{itemId}",
     * tags={"Cart (Buyer)"},
     * summary="Update Qty (+/-)",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="itemId", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="quantity", type="integer", example=3)
     * )
     * ),
     * @OA\Response(response=200, description="Qty berhasil diupdate"),
     * @OA\Response(response=400, description="Stok tidak mencukupi")
     * )
     */
    public function update(Request $request, $itemId)
    {
        $request->validate([
            'quantity' => 'required|integer|min:1'
        ]);

        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->first();
        
        // Cari item di keranjang user tersebut
        $item = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->first();

        if (!$item) {
            return response()->json(['message' => 'Item tidak ditemukan'], 404);
        }

        // Cek Stok Produk
        if ($item->product->stock < $request->quantity) {
            return response()->json(['message' => 'Stok tidak mencukupi. Sisa: ' . $item->product->stock], 400);
        }

        $item->quantity = $request->quantity;
        $item->save();

        return response()->json(['message' => 'Jumlah berhasil diupdate']);
    }
    
    /**
     * @OA\Delete(
     * path="/cart/item/{itemId}",
     * tags={"Cart (Buyer)"},
     * summary="Hapus Item dari Keranjang",
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(name="itemId", in="path", required=true, @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Item dihapus")
     * )
     */
    public function destroy(Request $request, $itemId)
    {
         // Pastikan item milik user yang sedang login
         $cart = Cart::where('user_id', $request->user()->id)->first();
         
         $deleted = CartItem::where('cart_id', $cart->id)->where('id', $itemId)->delete();
         
         if($deleted) {
             return response()->json(['message' => 'Item dihapus']);
         }
         
         return response()->json(['message' => 'Item tidak ditemukan'], 404);
    }
}