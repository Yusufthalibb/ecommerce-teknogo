<?php
// app/Http/Controllers/Admin/StockController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class StockController extends Controller
{
    /**
     * Display stock management
     */
    public function index(Request $request)
    {
        $query = Product::with('category');

        // Filter low stock
        if ($request->has('low_stock') && $request->low_stock) {
            $query->where('stock', '>', 0)->where('stock', '<', 10);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->search($request->search);
        }

        $products = $query->orderBy('stock', 'asc')->paginate(20);

        return view('admin.stock.index', compact('products'));
    }

    /**
     * Update single product stock
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'stock' => 'required|integer|min:0',
        ]);

        $oldStock = $product->stock;
        $product->update(['stock' => $validated['stock']]);

        $diff = $validated['stock'] - $oldStock;
        $action = $diff > 0 ? 'ditambah' : 'dikurangi';
        $amount = abs($diff);

        return back()->with('success', "Stok berhasil {$action} {$amount} unit");
    }

    /**
     * Bulk update stock
     */
    public function bulkUpdate(Request $request)
    {
        $validated = $request->validate([
            'products' => 'required|array',
            'products.*.id' => 'required|exists:products,id',
            'products.*.stock' => 'required|integer|min:0',
        ]);

        $updated = 0;

        foreach ($validated['products'] as $productData) {
            Product::where('id', $productData['id'])
                ->update(['stock' => $productData['stock']]);
            
            $updated++;
        }

        return back()->with('success', "{$updated} produk berhasil diperbarui");
    }
}