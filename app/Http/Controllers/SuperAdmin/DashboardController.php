<?php
// app/Http/Controllers/SuperAdmin/DashboardController.php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * Display super admin dashboard
     */
    public function index()
    {
        // Total Revenue (all completed orders)
        $totalRevenue = Order::where('status', 'completed')->sum('total');
        
        // Revenue this month
        $revenueThisMonth = Order::where('status', 'completed')
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('total');
        
        // Revenue this year
        $revenueThisYear = Order::where('status', 'completed')
            ->whereYear('created_at', now()->year)
            ->sum('total');
        
        // Total Orders
        $totalOrders = Order::count();
        
        // Total Customers
        $totalCustomers = User::where('role', 'customer')->count();
        
        // Total Products
        $totalProducts = Product::count();
        
        // Total Categories
        $totalCategories = Category::count();
        
        // Pending Payments (need verification)
        $pendingPayments = Payment::where('status', 'uploaded')->count();
        
        // Recent Orders
        $recentOrders = Order::with(['user', 'payment'])
            ->latest()
            ->limit(20)
            ->get();
        
        // Top Selling Products
        $topProducts = Product::with('category')
            ->select('products.*', DB::raw('SUM(order_items.quantity) as total_sold'))
            ->join('order_items', 'products.id', '=', 'order_items.product_id')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.status', 'completed')
            ->groupBy('products.id')
            ->orderBy('total_sold', 'desc')
            ->limit(10)
            ->get();
        
        // Monthly Revenue Chart (last 12 months)
        $monthlyRevenue = Order::where('status', 'completed')
            ->where('created_at', '>=', now()->subMonths(12))
            ->select(
                DB::raw('YEAR(created_at) as year'),
                DB::raw('MONTH(created_at) as month'),
                DB::raw('SUM(total) as revenue')
            )
            ->groupBy('year', 'month')
            ->orderBy('year', 'asc')
            ->orderBy('month', 'asc')
            ->get();
        
        // Order Status Chart
        $ordersByStatus = Order::select('status', DB::raw('COUNT(*) as count'))
            ->groupBy('status')
            ->get();

        return view('super-admin.dashboard', compact(
            'totalRevenue',
            'revenueThisMonth',
            'revenueThisYear',
            'totalOrders',
            'totalCustomers',
            'totalProducts',
            'totalCategories',
            'pendingPayments',
            'recentOrders',
            'topProducts',
            'monthlyRevenue',
            'ordersByStatus'
        ));
    }
}