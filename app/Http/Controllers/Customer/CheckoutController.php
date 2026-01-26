<?php
// app/Http/Controllers/Customer/CheckoutController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CheckoutController extends Controller
{
    /**
     * Display checkout page
     */
    public function index()
    {
        $cartItems = Cart::with('product')
            ->where('user_id', Auth::id())
            ->get();

        // Validasi cart tidak kosong
        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda kosong');
        }

        // Validasi semua produk masih available
        foreach ($cartItems as $item) {
            if (!$item->product->isAvailable($item->quantity)) {
                return redirect()->route('cart.index')
                    ->with('error', "Produk {$item->product->name} stok tidak mencukupi");
            }
        }

        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shippingCost = 20000; // Flat rate, bisa diganti dengan API ongkir
        $tax = 0; // Opsional
        $total = $subtotal + $shippingCost + $tax;

        $user = Auth::user();

        return view('customer.checkout.index', compact(
            'cartItems',
            'subtotal',
            'shippingCost',
            'tax',
            'total',
            'user'
        ));
    }

    /**
     * Process checkout
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'shipping_name' => 'required|string|max:255',
            'shipping_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string',
            'shipping_city' => 'nullable|string|max:100',
            'shipping_province' => 'nullable|string|max:100',
            'shipping_postal_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
        ]);

        // Get cart items
        $cartItems = Cart::with('product')
            ->where('user_id', Auth::id())
            ->get();

        if ($cartItems->isEmpty()) {
            return redirect()->route('cart.index')
                ->with('error', 'Keranjang Anda kosong');
        }

        // Calculate totals
        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        $shippingCost = 20000;
        $tax = 0;
        $total = $subtotal + $shippingCost + $tax;

        DB::beginTransaction();
        
        try {
            // Create order
            $order = Order::create([
                'user_id' => Auth::id(),
                'shipping_name' => $validated['shipping_name'],
                'shipping_phone' => $validated['shipping_phone'],
                'shipping_address' => $validated['shipping_address'],
                'shipping_city' => $validated['shipping_city'] ?? null,
                'shipping_province' => $validated['shipping_province'] ?? null,
                'shipping_postal_code' => $validated['shipping_postal_code'] ?? null,
                'subtotal' => $subtotal,
                'shipping_cost' => $shippingCost,
                'tax' => $tax,
                'total' => $total,
                'status' => 'pending',
                'notes' => $validated['notes'] ?? null,
            ]);

            // Create order items
            foreach ($cartItems as $cartItem) {
                // Cek stock lagi
                if (!$cartItem->product->isAvailable($cartItem->quantity)) {
                    throw new \Exception("Stok {$cartItem->product->name} tidak mencukupi");
                }

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $cartItem->product_id,
                    'product_name' => $cartItem->product->name,
                    'product_sku' => $cartItem->product->sku,
                    'price' => $cartItem->product->price,
                    'quantity' => $cartItem->quantity,
                    'subtotal' => $cartItem->product->price * $cartItem->quantity,
                ]);

                // Decrement stock
                $cartItem->product->decrementStock($cartItem->quantity);
            }

            // Create payment record
            Payment::create([
                'order_id' => $order->id,
                'amount' => $total,
                'status' => 'pending',
                'payment_method' => 'bank_transfer',
            ]);

            // Clear cart
            Cart::where('user_id', Auth::id())->delete();

            DB::commit();

            return redirect()->route('payment.show', $order->order_number)
                ->with('success', 'Pesanan berhasil dibuat. Silakan lakukan pembayaran.');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}