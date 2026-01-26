<?php
// app/Http/Controllers/Admin/DashboardController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;

class DashboardController extends Controller
{
    /**
     * Display admin dashboard
     */
    public function index()
    {
        // Total products
        $totalProducts = Product::count();
        
        // Products in stock
        $productsInStock = Product::where('stock', '>', 0)->count();
        
        // Products out of stock
        $productsOutOfStock = Product::where('stock', '<=', 0)->count();
        
        // Low stock products (stock < 10)
        $lowStockProducts = Product::with('category')
            ->where('stock', '>', 0)
            ->where('stock', '<', 10)
            ->orderBy('stock', 'asc')
            ->limit(10)
            ->get();
        
        // Recent orders
        $recentOrders = Order::with(['user', 'payment'])
            ->latest()
            ->limit(10)
            ->get();
        
        // Orders need processing
        $pendingOrders = Order::whereIn('status', ['confirmed', 'processing'])
            ->count();
        
        // Orders today
        $ordersToday = Order::whereDate('created_at', today())->count();

        return view('admin.dashboard', compact(
            'totalProducts',
            'productsInStock',
            'productsOutOfStock',
            'lowStockProducts',
            'recentOrders',
            'pendingOrders',
            'ordersToday'
        ));
    }
}