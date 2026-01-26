<?php
// app/Models/Category.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Category extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'icon',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // ==================== BOOT ====================
    
    protected static function boot()
    {
        parent::boot();

        // Auto generate slug saat create
        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });

        // Auto update slug saat update
        static::updating(function ($category) {
            if ($category->isDirty('name')) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    // ==================== RELATIONSHIPS ====================
    
    public function products()
    {
        return $this->hasMany(Product::class);
    }

    // ==================== ACCESSORS ====================
    
    public function getProductCountAttribute()
    {
        return $this->products()->count();
    }

    // ==================== SCOPES ====================
    
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeWithProductCount($query)
    {
        return $query->withCount('products');
    }

    // ==================== HELPER METHODS ====================
    
    public function activate()
    {
        $this->update(['is_active' => true]);
    }

    public function deactivate()
    {
        $this->update(['is_active' => false]);
    }
}