<?php
// database/migrations/2024_01_01_000005_create_orders_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_number')->unique(); // nomor invoice
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            
            // Shipping Information
            $table->string('shipping_name');
            $table->string('shipping_phone', 20);
            $table->text('shipping_address');
            $table->string('shipping_city')->nullable();
            $table->string('shipping_province')->nullable();
            $table->string('shipping_postal_code', 10)->nullable();
            
            // Order Details
            $table->decimal('subtotal', 15, 2); // total harga produk
            $table->decimal('shipping_cost', 15, 2)->default(0); // ongkir
            $table->decimal('tax', 15, 2)->default(0); // pajak jika ada
            $table->decimal('discount', 15, 2)->default(0); // diskon jika ada
            $table->decimal('total', 15, 2); // total keseluruhan
            
            // Status
            $table->enum('status', [
                'pending',           // menunggu pembayaran
                'payment_uploaded',  // bukti pembayaran sudah diupload
                'confirmed',         // pembayaran dikonfirmasi
                'processing',        // sedang diproses
                'shipped',           // sedang dikirim
                'delivered',         // sudah sampai
                'completed',         // transaksi selesai
                'cancelled'          // dibatalkan
            ])->default('pending');
            
            $table->text('notes')->nullable(); // catatan customer
            $table->text('admin_notes')->nullable(); // catatan admin
            
            $table->timestamp('confirmed_at')->nullable(); // waktu konfirmasi
            $table->timestamp('completed_at')->nullable(); // waktu selesai
            
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};