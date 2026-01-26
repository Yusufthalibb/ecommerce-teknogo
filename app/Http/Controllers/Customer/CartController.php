<?php
// app/Http/Controllers/Customer/CartController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Cart;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CartController extends Controller
{
    /**
     * Display shopping cart
     */
    public function index()
    {
        $cartItems = Cart::with('product.category')
            ->where('user_id', Auth::id())
            ->get();

        $subtotal = $cartItems->sum(function ($item) {
            return $item->product->price * $item->quantity;
        });

        return view('customer.cart.index', compact('cartItems', 'subtotal'));
    }

    /**
     * Add product to cart
     */
    public function add(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        // Cek stock
        if (!$product->isAvailable($validated['quantity'])) {
            return back()->with('error', 'Stok produk tidak mencukupi');
        }

        // Cek apakah produk sudah ada di cart
        $cartItem = Cart::where('user_id', Auth::id())
            ->where('product_id', $product->id)
            ->first();

        if ($cartItem) {
            // Update quantity
            $newQuantity = $cartItem->quantity + $validated['quantity'];
            
            if (!$product->isAvailable($newQuantity)) {
                return back()->with('error', 'Stok produk tidak mencukupi');
            }

            $cartItem->update(['quantity' => $newQuantity]);
        } else {
            // Create new cart item
            Cart::create([
                'user_id' => Auth::id(),
                'product_id' => $product->id,
                'quantity' => $validated['quantity'],
            ]);
        }

        return back()->with('success', 'Produk berhasil ditambahkan ke keranjang');
    }

    /**
     * Update cart item quantity
     */
    public function update(Request $request, Cart $cart)
    {
        // Authorize
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        // Cek stock
        if (!$cart->product->isAvailable($validated['quantity'])) {
            return back()->with('error', 'Stok produk tidak mencukupi');
        }

        $cart->update(['quantity' => $validated['quantity']]);

        return back()->with('success', 'Keranjang berhasil diperbarui');
    }

    /**
     * Remove item from cart
     */
    public function destroy(Cart $cart)
    {
        // Authorize
        if ($cart->user_id !== Auth::id()) {
            abort(403);
        }

        $cart->delete();

        return back()->with('success', 'Produk berhasil dihapus dari keranjang');
    }

    /**
     * Clear all cart items
     */
    public function clear()
    {
        Cart::where('user_id', Auth::id())->delete();

        return back()->with('success', 'Keranjang berhasil dikosongkan');
    }
}