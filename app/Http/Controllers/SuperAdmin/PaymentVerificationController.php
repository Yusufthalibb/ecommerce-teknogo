<?php
// app/Http/Controllers/SuperAdmin/PaymentVerificationController.php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PaymentVerificationController extends Controller
{
    /**
     * Display payments need verification
     */
    public function index(Request $request)
    {
        $query = Payment::with(['order.user'])
            ->where('status', 'uploaded');

        // Search
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhereHas('order', function ($q) use ($search) {
                      $q->where('order_number', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->latest()->paginate(20);

        return view('super-admin.payments.index', compact('payments'));
    }

    /**
     * Show payment detail for verification
     */
    public function show(Payment $payment)
    {
        $payment->load(['order.user', 'order.orderItems.product']);
        
        return view('super-admin.payments.show', compact('payment'));
    }

    /**
     * Verify payment
     */
    public function verify(Payment $payment)
    {
        if ($payment->status !== 'uploaded') {
            return back()->with('error', 'Pembayaran ini tidak dapat diverifikasi');
        }

        $payment->verify(Auth::id());

        return redirect()->route('super-admin.payments.index')
            ->with('success', 'Pembayaran berhasil diverifikasi');
    }

    /**
     * Reject payment
     */
    public function reject(Request $request, Payment $payment)
    {
        if ($payment->status !== 'uploaded') {
            return back()->with('error', 'Pembayaran ini tidak dapat ditolak');
        }

        $validated = $request->validate([
            'rejection_reason' => 'required|string',
        ], [
            'rejection_reason.required' => 'Alasan penolakan wajib diisi',
        ]);

        $payment->reject($validated['rejection_reason'], Auth::id());

        return redirect()->route('super-admin.payments.index')
            ->with('success', 'Pembayaran berhasil ditolak');
    }
}