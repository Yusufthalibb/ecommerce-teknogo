<?php
// app/Http/Controllers/Admin/ProductController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display product listing
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filter by category
        if ($request->has('category') && $request->category) {
            $query->where('category_id', $request->category);
        }

        // Filter by stock status
        if ($request->has('stock_status')) {
            switch ($request->stock_status) {
                case 'in_stock':
                    $query->where('stock', '>', 0);
                    break;
                case 'out_of_stock':
                    $query->where('stock', '<=', 0);
                    break;
                case 'low_stock':
                    $query->where('stock', '>', 0)->where('stock', '<', 10);
                    break;
            }
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $products = $query->latest()->paginate(20);
        $categories = Category::active()->get();

        return view('admin.products.index', compact('products', 'categories'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        $categories = Category::active()->get();
        return view('admin.products.create', compact('categories'));
    }

    /**
     * Store new product
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:products,name',
            'description' => 'nullable|string',
            'specifications' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:100',
            'sku' => 'nullable|string|unique:products,sku',
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Auto generate slug
        $validated['slug'] = Str::slug($validated['name']);

        // Auto generate SKU if not provided
        if (empty($validated['sku'])) {
            $validated['sku'] = 'PRD-' . strtoupper(Str::random(8));
        }

        // Convert specifications to JSON if needed
        if (isset($validated['specifications'])) {
            $validated['specifications'] = $validated['specifications'];
        }

        Product::create($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan');
    }

    /**
     * Show product detail
     */
    public function show(Product $product)
    {
        $product->load(['category', 'images', 'orderItems']);
        
        return view('admin.products.show', compact('product'));
    }

    /**
     * Show edit form
     */
    public function edit(Product $product)
    {
        $categories = Category::active()->get();
        
        return view('admin.products.edit', compact('product', 'categories'));
    }

    /**
     * Update product
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255|unique:products,name,' . $product->id,
            'description' => 'nullable|string',
            'specifications' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'brand' => 'nullable|string|max:100',
            'sku' => 'nullable|string|unique:products,sku,' . $product->id,
            'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            
            $validated['image'] = $request->file('image')->store('products', 'public');
        }

        // Update slug if name changed
        if ($product->name !== $validated['name']) {
            $validated['slug'] = Str::slug($validated['name']);
        }

        $product->update($validated);

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui');
    }

    /**
     * Delete product
     */
    public function destroy(Product $product)
    {
        // Cek apakah produk ada di order yang aktif
        $hasActiveOrders = $product->orderItems()
            ->whereHas('order', function ($query) {
                $query->whereNotIn('status', ['cancelled', 'completed']);
            })
            ->exists();

        if ($hasActiveOrders) {
            return back()->with('error', 'Produk tidak dapat dihapus karena masih ada di order yang aktif');
        }

        // Delete image
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }

        // Soft delete
        $product->delete();

        return redirect()->route('admin.products.index')
            ->with('success', 'Produk berhasil dihapus');
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus(Product $product)
    {
        $product->update([
            'is_active' => !$product->is_active
        ]);

        $status = $product->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Produk berhasil {$status}");
    }

    /**
     * Toggle product featured status
     */
    public function toggleFeatured(Product $product)
    {
        $product->update([
            'is_featured' => !$product->is_featured
        ]);

        $status = $product->is_featured ? 'ditambahkan ke' : 'dihapus dari';

        return back()->with('success', "Produk berhasil {$status} produk unggulan");
    }
}