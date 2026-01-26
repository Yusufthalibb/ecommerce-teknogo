<?php
// app/Models/ProductImage.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'image_path',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ==================== ACCESSORS ====================
    
    public function getImageUrlAttribute()
    {
        return asset('storage/' . $this->image_path);
    }

    // ==================== SCOPES ====================
    
    public function scopePrimary($query)
    {
        return $query->where('is_primary', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('order');
    }
}