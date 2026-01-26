<?php
// app/Http/Controllers/SuperAdmin/CategoryController.php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * Display categories
     */
    public function index()
    {
        $categories = Category::withCount('products')
            ->latest()
            ->paginate(15);

        return view('super-admin.categories.index', compact('categories'));
    }

    /**
     * Show create form
     */
    public function create()
    {
        return view('super-admin.categories.create');
    }

    /**
     * Store new category
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        Category::create($validated);

        return redirect()->route('super-admin.categories.index')
            ->with('success', 'Kategori berhasil ditambahkan');
    }

    /**
     * Show category detail
     */
    public function show(Category $category)
    {
        $category->load('products');
        
        return view('super-admin.categories.show', compact('category'));
    }

    /**
     * Show edit form
     */
    public function edit(Category $category)
    {
        return view('super-admin.categories.edit', compact('category'));
    }

    /**
     * Update category
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable|string',
            'icon' => 'nullable|string|max:100',
            'is_active' => 'boolean',
        ]);

        $validated['slug'] = Str::slug($validated['name']);

        $category->update($validated);

        return redirect()->route('super-admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui');
    }

    /**
     * Delete category
     */
    public function destroy(Category $category)
    {
        // Cek apakah ada produk
        if ($category->products()->count() > 0) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki produk');
        }

        $category->delete();

        return redirect()->route('super-admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus');
    }

    /**
     * Toggle category status
     */
    public function toggleStatus(Category $category)
    {
        $category->update([
            'is_active' => !$category->is_active
        ]);

        $status = $category->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Kategori berhasil {$status}");
    }
}