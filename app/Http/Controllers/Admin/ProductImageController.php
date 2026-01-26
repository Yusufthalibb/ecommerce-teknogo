<?php
// app/Http/Controllers/Admin/ProductImageController.php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ProductImageController extends Controller
{
    /**
     * Display product images management
     */
    public function index(Product $product)
    {
        $product->load('images');
        
        return view('admin.products.images', compact('product'));
    }

    /**
     * Upload additional images
     */
    public function store(Request $request, Product $product)
    {
        $validated = $request->validate([
            'images' => 'required|array|min:1',
            'images.*' => 'image|mimes:jpg,jpeg,png|max:2048',
        ]);

        $uploadedCount = 0;

        foreach ($request->file('images') as $index => $image) {
            $path = $image->store('products/gallery', 'public');
            
            ProductImage::create([
                'product_id' => $product->id,
                'image_path' => $path,
                'is_primary' => false,
                'order' => $product->images()->count() + $uploadedCount,
            ]);

            $uploadedCount++;
        }

        return back()->with('success', "{$uploadedCount} gambar berhasil diupload");
    }

    /**
     * Set image as primary
     */
    public function setPrimary(ProductImage $image)
    {
        // Set semua gambar produk ini jadi bukan primary
        ProductImage::where('product_id', $image->product_id)
            ->update(['is_primary' => false]);

        // Set gambar ini sebagai primary
        $image->update(['is_primary' => true]);

        return back()->with('success', 'Gambar utama berhasil diubah');
    }

    /**
     * Delete image
     */
    public function destroy(ProductImage $image)
    {
        // Delete file
        Storage::disk('public')->delete($image->image_path);

        // Delete record
        $image->delete();

        return back()->with('success', 'Gambar berhasil dihapus');
    }
}