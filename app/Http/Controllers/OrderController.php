<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     * path="/checkout",
     * tags={"Order & Payment"},
     * summary="Checkout (Buat Invoice Xendit)",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="shipping_service", type="string", example="REG"),
     * @OA\Property(property="shipping_cost", type="integer", example=20000),
     * @OA\Property(property="courier", type="string", example="jne")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Sukses, dapat Link Payment",
     * @OA\JsonContent(
     * @OA\Property(property="payment_url", type="string", example="https://checkout-staging.xendit.co/...")
     * )
     * )
     * )
     */
    public function checkout(Request $request, XenditService $xenditService)
    {
        $request->validate([
            'shipping_service' => 'required|string', // misal: "REG"
            'shipping_cost' => 'required|numeric',   // misal: 20000
            'courier' => 'required|string',          // misal: "jne"
        ]);

        $user = $request->user();

        // 1. Ambil Keranjang
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->count() == 0) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        // 2. Hitung Total Harga Barang
        $totalItemPrice = 0;
        foreach ($cart->items as $item) {
            $totalItemPrice += $item->product->price * $item->quantity;
        }

        $grandTotal = $totalItemPrice + $request->shipping_cost;

        // Gunakan DB Transaction agar kalau Xendit gagal, data order tidak masuk
        return DB::transaction(function () use ($request, $user, $cart, $totalItemPrice, $grandTotal, $xenditService) {
            
            // 3. Simpan Order ke Database
            $order = Order::create([
                'user_id' => $user->id,
                'shipping_service' => $request->shipping_service,
                'shipping_cost' => $request->shipping_cost,
                'courier' => $request->courier,
                'total_price' => $grandTotal,
                'status' => 'PENDING',
                'xendit_invoice_id' => null, // Nanti diisi
                'xendit_invoice_url' => null, // Nanti diisi
            ]);

            // 4. Pindahkan Item Keranjang ke Order Item & SIAPKAN ITEM XENDIT
            $xenditItems = []; // Array untuk dikirim ke Xendit

            foreach ($cart->items as $item) {
                // Simpan ke Database
                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price, 
                ]);

                // Masukkan ke Array Xendit
                $xenditItems[] = [
                    'name' => $item->product->name,
                    'quantity' => $item->quantity,
                    'price' => $item->product->price,
                    'category' => 'Product'
                ];
            }

            // 5. Tambahkan Ongkir sebagai Item Xendit (Agar muncul di Invoice)
            $xenditItems[] = [
                'name' => 'Ongkir (' . strtoupper($request->courier) . ' - ' . $request->shipping_service . ')',
                'quantity' => 1,
                'price' => $request->shipping_cost,
                'category' => 'Shipping'
            ];

            // 6. Panggil Xendit
            $externalId = 'ORDER-' . $order->id . '-' . time();
            $description = "Pembayaran Order #" . $order->id;

            try {
                // Panggil Service dengan parameter ke-5 ($xenditItems)
                $xenditResponse = $xenditService->createInvoice(
                    $externalId,
                    $grandTotal,
                    $user->email,
                    $description,
                    $xenditItems // <--- LOGIC BARU DISINI
                );

                // Update Order dengan data Xendit
                $order->update([
                    'xendit_invoice_id' => $xenditResponse['id'],
                    'xendit_invoice_url' => $xenditResponse['invoice_url']
                ]);

            } catch (\Exception $e) {
                // Jika Xendit error, return error (DB Transaction rollback otomatis)
                throw new \Exception("Gagal membuat invoice Xendit: " . $e->getMessage());
            }

            // 7. Kosongkan Keranjang
            $cart->items()->delete();

            return response()->json([
                'message' => 'Order berhasil dibuat',
                'data' => $order,
                'payment_url' => $order->xendit_invoice_url 
            ]);
        });
    }

    /**
     * @OA\Get(
     * path="/orders",
     * tags={"Order & Payment"},
     * summary="History Order Saya",
     * security={{"bearerAuth":{}}},
     * @OA\Response(response=200, description="List History Order")
     * )
     */
    public function index(Request $request)
    {
        $orders = Order::where('user_id', $request->user()->id)->latest()->get();
        return response()->json(['data' => $orders]);
    }
}