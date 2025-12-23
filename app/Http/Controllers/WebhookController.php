<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WebhookController extends Controller
{
    /**
     * @OA\Post(
     * path="/webhooks/xendit",
     * tags={"Webhook"},
     * summary="Menerima Callback dari Xendit (Otomatis)",
     * description="Endpoint ini dipanggil oleh server Xendit, bukan oleh Frontend Flutter",
     * @OA\Response(response=200, description="OK")
     * )
     */
    public function handle(Request $request)
    {
        // 1. Verifikasi Token Xendit (Security) - Opsional buat Localhost, Wajib buat Production
        // $xenditCallbackToken = env('XENDIT_CALLBACK_TOKEN');
        // if ($request->header('x-callback-token') !== $xenditCallbackToken) {
        //     return response()->json(['message' => 'Invalid Token'], 403);
        // }

        // 2. Ambil Data dari Xendit
        $data = $request->all();
        
        // Pastikan ini callback Invoice
        if (!isset($data['external_id']) || !isset($data['status'])) {
            return response()->json(['message' => 'Invalid Data'], 400);
        }

        // Format External ID: "ORDER-1-12345678"
        // Kita butuh ambil ID Ordernya saja (angka "1")
        $externalIdParts = explode('-', $data['external_id']);
        $orderId = $externalIdParts[1]; // Ambil bagian tengah

        $order = Order::find($orderId);

        if (!$order) {
            return response()->json(['message' => 'Order not found'], 404);
        }

        // 3. Logic Jika Status PAID / SETTLED
        if ($order->status !== 'PAID' && ($data['status'] === 'PAID' || $data['status'] === 'SETTLED')) {
            
            DB::transaction(function () use ($order) {
                // A. Update Status Order
                $order->update(['status' => 'PAID']);

                // B. POTONG STOK
                foreach ($order->items as $item) {
                    $product = Product::find($item->product_id);
                    if ($product) {
                        $product->decrement('stock', $item->quantity);
                    }
                }
            });
        }

        return response()->json(['message' => 'Webhook received']);
    }
}