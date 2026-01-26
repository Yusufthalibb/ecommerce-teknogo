<?php
// app/Models/Cart.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Cart extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'integer',
    ];

    // ==================== RELATIONSHIPS ====================
    
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // ==================== ACCESSORS ====================
    
    public function getSubtotalAttribute()
    {
        return $this->product->price * $this->quantity;
    }

    public function getFormattedSubtotalAttribute()
    {
        return 'Rp ' . number_format($this->subtotal, 0, ',', '.');
    }

    // ==================== HELPER METHODS ====================
    
    public function incrementQuantity($amount = 1)
    {
        $newQuantity = $this->quantity + $amount;
        
        if ($this->product->stock >= $newQuantity) {
            $this->increment('quantity', $amount);
            return true;
        }
        
        return false;
    }

    public function decrementQuantity($amount = 1)
    {
        if ($this->quantity > $amount) {
            $this->decrement('quantity', $amount);
            return true;
        }
        
        return false;
    }

    public function updateQuantity($quantity)
    {
        if ($this->product->stock >= $quantity && $quantity > 0) {
            $this->update(['quantity' => $quantity]);
            return true;
        }
        
        return false;
    }
}