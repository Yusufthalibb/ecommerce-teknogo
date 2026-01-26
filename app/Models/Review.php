<?php
// app/Models/Review.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'user_id',
        'order_id',
        'rating',
        'review',
        'is_approved',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_approved' => 'boolean',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // ==================== SCOPES ====================
    
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    public function scopeLatestReviews($query, $limit = 10)
    {
        return $query->latest()->limit($limit);
    }

    // ==================== HELPER METHODS ====================
    
    public function approve()
    {
        $this->update(['is_approved' => true]);
    }

    public function disapprove()
    {
        $this->update(['is_approved' => false]);
    }
}