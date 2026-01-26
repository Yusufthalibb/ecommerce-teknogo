<?php
// app/Http/Controllers/Customer/HomeController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;

class HomeController extends Controller
{
    /**
     * Display homepage
     */
    public function index()
    {
        // Featured products
        $featuredProducts = Product::with('category')
            ->active()
            ->featured()
            ->inStock()
            ->limit(8)
            ->get();

        // Latest products
        $latestProducts = Product::with('category')
            ->active()
            ->inStock()
            ->latest()
            ->limit(12)
            ->get();

        // Popular products (berdasarkan views)
        $popularProducts = Product::with('category')
            ->active()
            ->inStock()
            ->popular(8)
            ->get();

        // Categories
        $categories = Category::active()
            ->withCount('products')
            ->get();

        return view('customer.home', compact(
            'featuredProducts',
            'latestProducts',
            'popularProducts',
            'categories'
        ));
    }
}