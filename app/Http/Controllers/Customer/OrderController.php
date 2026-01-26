<?php
// app/Http/Controllers/Customer/OrderController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    /**
     * Display user's orders
     */
    public function index()
    {
        $orders = Order::with('payment')
            ->where('user_id', Auth::id())
            ->latest()
            ->paginate(10);

        return view('customer.orders.index', compact('orders'));
    }

    /**
     * Display order detail
     */
    public function show($orderNumber)
    {
        $order = Order::with(['orderItems.product', 'payment'])
            ->where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('customer.orders.show', compact('order'));
    }

    /**
     * Cancel order
     */
    public function cancel(Order $order)
    {
        // Authorize
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // Cek apakah order bisa di-cancel
        if (!$order->canBeCancelled()) {
            return back()->with('error', 'Pesanan tidak dapat dibatalkan');
        }

        DB::beginTransaction();
        
        try {
            // Update order status
            $order->updateStatus('cancelled', 'Dibatalkan oleh customer');

            // Restore stock
            foreach ($order->orderItems as $item) {
                $item->product->incrementStock($item->quantity);
            }

            DB::commit();

            return back()->with('success', 'Pesanan berhasil dibatalkan');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }
}