<?php
// app/Http/Controllers/Customer/ReviewController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ReviewController extends Controller
{
    /**
     * Show review form
     */
    public function create($orderNumber, $productId)
    {
        $order = Order::where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->where('status', 'completed')
            ->firstOrFail();

        // Cek apakah produk ada di order
        $orderItem = $order->orderItems()
            ->where('product_id', $productId)
            ->firstOrFail();

        $product = Product::findOrFail($productId);

        // Cek apakah sudah pernah review
        $existingReview = Review::where('order_id', $order->id)
            ->where('product_id', $productId)
            ->where('user_id', Auth::id())
            ->first();

        if ($existingReview) {
            return redirect()->route('orders.show', $orderNumber)
                ->with('error', 'Anda sudah memberikan review untuk produk ini');
        }

        return view('customer.reviews.create', compact('order', 'product'));
    }

    /**
     * Store review
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
'product_id' => 'required|exists:products,id',
'order_id' => 'required|exists:orders,id',
'rating' => 'required|integer|min:1|max:5',
'review' => 'nullable|string',
]);
    // Authorize order
    $order = Order::where('id', $validated['order_id'])
        ->where('user_id', Auth::id())
        ->where('status', 'completed')
        ->firstOrFail();

    // Cek duplikasi
    $existing = Review::where('order_id', $order->id)
        ->where('product_id', $validated['product_id'])
        ->where('user_id', Auth::id())
        ->first();

    if ($existing) {
        return back()->with('error', 'Anda sudah memberikan review untuk produk ini');
    }

    Review::create([
        'product_id' => $validated['product_id'],
        'user_id' => Auth::id(),
        'order_id' => $order->id,
        'rating' => $validated['rating'],
        'review' => $validated['review'],
        'is_approved' => false, // Menunggu approval
    ]);

    return redirect()->route('orders.show', $order->order_number)
        ->with('success', 'Review berhasil dikirim. Menunggu persetujuan admin.');
}

/**
 * Show edit form
 */
public function edit(Review $review)
{
    // Authorize
    if ($review->user_id !== Auth::id()) {
        abort(403);
    }

    return view('customer.reviews.edit', compact('review'));
}

/**
 * Update review
 */
public function update(Request $request, Review $review)
{
    // Authorize
    if ($review->user_id !== Auth::id()) {
        abort(403);
    }

    $validated = $request->validate([
        'rating' => 'required|integer|min:1|max:5',
        'review' => 'nullable|string',
    ]);

    $review->update([
        'rating' => $validated['rating'],
        'review' => $validated['review'],
        'is_approved' => false, // Butuh approval lagi
    ]);

    return redirect()->route('orders.show', $review->order->order_number)
        ->with('success', 'Review berhasil diperbarui. Menunggu persetujuan admin.');
}

/**
 * Delete review
 */
public function destroy(Review $review)
{
    // Authorize
    if ($review->user_id !== Auth::id()) {
        abort(403);
    }

    $review->delete();

    return back()->with('success', 'Review berhasil dihapus');
}
}