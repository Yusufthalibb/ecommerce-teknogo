<?php
// app/Models/Payment.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'bank_name',
        'account_number',
        'account_name',
        'amount',
        'proof_image',
        'status',
        'notes',
        'rejection_reason',
        'verified_by',
        'verified_at',
        'uploaded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'uploaded_at' => 'datetime',
    ];

    // ==================== BOOT ====================
    
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($payment) {
            if (empty($payment->payment_number)) {
                $payment->payment_number = 'PAY-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            }
        });
    }

    // ==================== RELATIONSHIPS ====================
    
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    // ==================== ACCESSORS ====================
    
    public function getFormattedAmountAttribute()
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    public function getProofImageUrlAttribute()
    {
        return $this->proof_image ? asset('storage/' . $this->proof_image) : null;
    }

    public function getStatusLabelAttribute()
    {
        return match($this->status) {
            'pending' => 'Menunggu Upload',
            'uploaded' => 'Sudah Diupload',
            'verified' => 'Terverifikasi',
            'rejected' => 'Ditolak',
            default => 'Unknown'
        };
    }

    public function getStatusColorAttribute()
    {
        return match($this->status) {
            'pending' => 'warning',
            'uploaded' => 'info',
            'verified' => 'success',
            'rejected' => 'error',
            default => 'neutral'
        };
    }

    // ==================== SCOPES ====================
    
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeUploaded($query)
    {
        return $query->where('status', 'uploaded');
    }

    public function scopeVerified($query)
    {
        return $query->where('status', 'verified');
    }

    // ==================== HELPER METHODS ====================
    
    public function verify($userId)
    {
        $this->update([
            'status' => 'verified',
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
        
        // Update order status
        $this->order->updateStatus('confirmed');
    }

    public function reject($reason, $userId)
    {
        $this->update([
            'status' => 'rejected',
            'rejection_reason' => $reason,
            'verified_by' => $userId,
            'verified_at' => now(),
        ]);
    }

    public function isVerified(): bool
    {
        return $this->status === 'verified';
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }
}