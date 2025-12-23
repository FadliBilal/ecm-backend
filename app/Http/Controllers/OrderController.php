<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Services\XenditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

/**
 * @OA\Tag(
 * name="Order & Payment",
 * description="API untuk Checkout, Order, dan Payment Gateway"
 * )
 */
class OrderController extends Controller
{
    /**
     * @OA\Post(
     * path="/orders",
     * tags={"Order & Payment"},
     * summary="Buat Order Baru (Checkout)",
     * description="Mengubah keranjang menjadi order dan membuat invoice Xendit",
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * required={"shipping_service", "shipping_cost", "courier"},
     * @OA\Property(property="shipping_service", type="string", example="REG", description="Layanan pengiriman (REG, YES, OKE)"),
     * @OA\Property(property="shipping_cost", type="integer", example=20000, description="Biaya ongkir dalam Rupiah"),
     * @OA\Property(property="courier", type="string", example="jne", description="Kode kurir (jne, pos, tiki)"),
     * @OA\Property(property="address", type="string", example="Jl. Mulyorejo No. 10, Surabaya", description="Alamat pengiriman (Opsional, jika null ambil dari profil)"),
     * @OA\Property(property="phone", type="string", example="08123456789", description="No HP penerima (Opsional)"),
     * @OA\Property(property="notes", type="string", example="Tolong packing kayu", description="Catatan pesanan (Opsional)")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Order Berhasil Dibuat",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Order berhasil dibuat"),
     * @OA\Property(property="data", type="object", description="Detail Order Object"),
     * @OA\Property(property="payment_url", type="string", example="https://checkout-staging.xendit.co/web/606...", description="Link pembayaran Xendit")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Validasi Gagal atau Keranjang Kosong",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Keranjang kosong")
     * )
     * ),
     * @OA\Response(
     * response=500,
     * description="Internal Server Error (Gagal membuat invoice)",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Gagal membuat order"),
     * @OA\Property(property="error", type="string", example="Xendit Error...")
     * )
     * )
     * )
     */
    public function store(Request $request, XenditService $xenditService)
    {
        // 1. Validasi Input
        $request->validate([
            'shipping_service' => 'required|string',
            'shipping_cost'    => 'required|integer|min:0',
            'courier'          => 'required|string',
            'address'          => 'nullable|string',
            'phone'            => 'nullable|string',
            'notes'            => 'nullable|string',
        ]);

        $user = $request->user();

        // 2. Ambil Keranjang
        $cart = Cart::where('user_id', $user->id)->with('items.product')->first();

        if (!$cart || $cart->items->count() == 0) {
            return response()->json(['message' => 'Keranjang kosong'], 400);
        }

        // Cek Stok Produk
        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                return response()->json([
                    'message' => "Stok produk '{$item->product->name}' tidak mencukupi."
                ], 400);
            }
        }

        $totalItemPrice = 0;
        foreach ($cart->items as $item) {
            $totalItemPrice += $item->product->price * $item->quantity;
        }

        $grandTotal = $totalItemPrice + $request->shipping_cost;

        // 3. Transaksi DB
        try {
            return DB::transaction(function () use ($request, $user, $cart, $grandTotal, $xenditService) {
                
                // A. Create Order
                $order = Order::create([
                    'user_id'          => $user->id,
                    'order_number'     => 'ORD-' . time() . '-' . mt_rand(1000, 9999),
                    'shipping_service' => $request->shipping_service,
                    'shipping_cost'    => $request->shipping_cost,
                    'courier'          => $request->courier,
                    'total_price'      => $grandTotal,
                    'status'           => 'PENDING',
                    'payment_method'   => 'xendit',
                    'address'          => $request->address ?? $user->address ?? '-',
                    'phone'            => $request->phone ?? $user->phone ?? '-',
                    'notes'            => $request->notes,
                    'xendit_invoice_id' => null,
                    'xendit_invoice_url'=> null,
                ]);

                $xenditItems = [];

                // B. Move Cart to OrderItem
                foreach ($cart->items as $item) {
                    OrderItem::create([
                        'order_id'   => $order->id,
                        'product_id' => $item->product_id,
                        'quantity'   => $item->quantity,
                        'price'      => $item->product->price,
                    ]);

                    // Kurangi Stok
                    $item->product->decrement('stock', $item->quantity);

                    $xenditItems[] = [
                        'name'     => $item->product->name,
                        'quantity' => $item->quantity,
                        'price'    => $item->product->price,
                        'category' => 'Product'
                    ];
                }

                // C. Add Shipping to Xendit Items
                $xenditItems[] = [
                    'name'     => 'Ongkir (' . strtoupper($request->courier) . ' - ' . $request->shipping_service . ')',
                    'quantity' => 1,
                    'price'    => $request->shipping_cost,
                    'category' => 'Shipping'
                ];

                // D. Call Xendit
                $externalId = (string) $order->order_number;
                $description = "Pembayaran Order #" . $order->order_number;

                $xenditResponse = $xenditService->createInvoice(
                    $externalId,
                    $grandTotal,
                    $user->email,
                    $description,
                    $xenditItems
                );

                // E. Update Order with Invoice URL
                $order->update([
                    'xendit_invoice_id'  => $xenditResponse['id'],
                    'xendit_invoice_url' => $xenditResponse['invoice_url']
                ]);

                // F. Clear Cart
                $cart->items()->delete();

                return response()->json([
                    'message'     => 'Order berhasil dibuat',
                    'data'        => $order,
                    'payment_url' => $order->xendit_invoice_url
                ], 201);
            });

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Gagal membuat order',
                'error'   => $e->getMessage()
            ], 500);
        }
    }

    /**
     * @OA\Get(
     * path="/orders",
     * tags={"Order & Payment"},
     * summary="List History Order",
     * description="Mendapatkan daftar riwayat pesanan user yang sedang login",
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Berhasil mendapatkan data",
     * @OA\JsonContent(
     * @OA\Property(property="data", type="array", @OA\Items(type="object", description="Order Data"))
     * )
     * )
     * )
     */
    public function index(Request $request, XenditService $xenditService)
    {
        // 1. Ambil semua order user
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest()
            ->get();

        // 2. CEK STATUS KE XENDIT (Jemput Bola)
        foreach ($orders as $order) {
            // Hanya cek yang masih PENDING dan punya Invoice ID
            if ($order->status == 'PENDING' && $order->xendit_invoice_id) {
                try {
                    // Tanya Xendit: "Order ini statusnya apa?"
                    $xenditData = $xenditService->getInvoice($order->xendit_invoice_id);
                    
                    // Kalau status di Xendit sudah PAID atau SETTLED
                    if ($xenditData['status'] == 'PAID' || $xenditData['status'] == 'SETTLED') {
                        // Update Database Kita
                        $order->update(['status' => 'PAID']);
                    } else if ($xenditData['status'] == 'EXPIRED') {
                        $order->update(['status' => 'EXPIRED']);
                    }
                } catch (\Exception $e) {
                    // Kalau error cek status, biarkan saja (jangan bikin crash)
                }
            }
        }

        // 3. Kembalikan data yang sudah ter-update (fresh)
        // Kita refresh variable $orders biar status PAID-nya muncul
        $orders = Order::where('user_id', $request->user()->id)
            ->with(['items.product'])
            ->latest()
            ->get();

        return response()->json(['data' => $orders]);
    }
}