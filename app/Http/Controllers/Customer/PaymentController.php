<?php
// app/Http/Controllers/Customer/PaymentController.php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class PaymentController extends Controller
{
    /**
     * Show payment upload page
     */
    public function show($orderNumber)
    {
        $order = Order::with('payment')
            ->where('order_number', $orderNumber)
            ->where('user_id', Auth::id())
            ->firstOrFail();

        return view('customer.payment.upload', compact('order'));
    }

    /**
     * Upload payment proof
     */
    public function upload(Request $request, Order $order)
    {
        // Authorize
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        $validated = $request->validate([
            'proof_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'payment_method' => 'required|in:bank_transfer,e_wallet,cod,other',
            'bank_name' => 'required_if:payment_method,bank_transfer|nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ], [
            'proof_image.required' => 'Bukti pembayaran wajib diupload',
            'proof_image.image' => 'File harus berupa gambar',
            'proof_image.max' => 'Ukuran file maksimal 2MB',
        ]);

        // Upload image
        $imagePath = $request->file('proof_image')->store('payments', 'public');

        // Update payment
        $order->payment->update([
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'proof_image' => $imagePath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'uploaded',
            'uploaded_at' => now(),
        ]);

        // Update order status
        $order->updateStatus('payment_uploaded');

        return redirect()->route('orders.show', $order->order_number)
            ->with('success', 'Bukti pembayaran berhasil diupload. Menunggu verifikasi admin.');
    }

    /**
     * Re-upload payment if rejected
     */
    public function reupload(Request $request, Order $order)
    {
        // Authorize
        if ($order->user_id !== Auth::id()) {
            abort(403);
        }

        // Cek apakah payment status = rejected
        if ($order->payment->status !== 'rejected') {
            return back()->with('error', 'Anda tidak dapat mengupload ulang pembayaran');
        }

        $validated = $request->validate([
            'proof_image' => 'required|image|mimes:jpg,jpeg,png|max:2048',
            'payment_method' => 'required|in:bank_transfer,e_wallet,cod,other',
            'bank_name' => 'required_if:payment_method,bank_transfer|nullable|string',
            'account_number' => 'nullable|string',
            'account_name' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        // Delete old image
        if ($order->payment->proof_image) {
            Storage::disk('public')->delete($order->payment->proof_image);
        }

        // Upload new image
        $imagePath = $request->file('proof_image')->store('payments', 'public');

        // Update payment
        $order->payment->update([
            'payment_method' => $validated['payment_method'],
            'bank_name' => $validated['bank_name'] ?? null,
            'account_number' => $validated['account_number'] ?? null,
            'account_name' => $validated['account_name'] ?? null,
            'proof_image' => $imagePath,
            'notes' => $validated['notes'] ?? null,
            'status' => 'uploaded',
            'uploaded_at' => now(),
            'rejection_reason' => null,
        ]);

        // Update order status
        $order->updateStatus('payment_uploaded');

        return redirect()->route('orders.show', $order->order_number)
            ->with('success', 'Bukti pembayaran berhasil diupload ulang. Menunggu verifikasi admin.');
    }
}