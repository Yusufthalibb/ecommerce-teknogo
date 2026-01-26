<?php
// app/Http/Controllers/Customer/ProductController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display product listing
     */
    public function index(Request $request)
    {
        $query = Product::with('category')->active();

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->byCategory($request->category);
        }

        // Filter by price range
        if ($request->has('min_price') || $request->has('max_price')) {
            $query->priceRange($request->min_price, $request->max_price);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        // Sort
        $sort = $request->get('sort', 'latest');
        switch ($sort) {
            case 'price_low':
                $query->orderBy('price', 'asc');
                break;
            case 'price_high':
                $query->orderBy('price', 'desc');
                break;
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            case 'name':
                $query->orderBy('name', 'asc');
                break;
            default:
                $query->latest();
        }

        $products = $query->paginate(12);
        $categories = Category::active()->withCount('products')->get();

        return view('customer.products.index', compact('products', 'categories'));
    }

    /**
     * Display product detail
     */
    public function show($slug)
    {
        $product = Product::with(['category', 'images', 'reviews.user'])
            ->where('slug', $slug)
            ->active()
            ->firstOrFail();

        // Increment views
        $product->incrementViews();

        // Related products (same category)
        $relatedProducts = Product::with('category')
            ->where('category_id', $product->category_id)
            ->where('id', '!=', $product->id)
            ->active()
            ->inStock()
            ->limit(4)
            ->get();

        return view('customer.products.show', compact('product', 'relatedProducts'));
    }

    /**
     * Search products (AJAX)
     */
    public function search(Request $request)
    {
        $keyword = $request->get('q');

        if (!$keyword) {
            return response()->json([
                'success' => false,
                'message' => 'Keyword required'
            ], 400);
        }

        $products = Product::with('category')
            ->active()
            ->search($keyword)
            ->limit(10)
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->formatted_price,
                    'image' => $product->image_url,
                    'category' => $product->category->name,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $products
        ]);
    }
}