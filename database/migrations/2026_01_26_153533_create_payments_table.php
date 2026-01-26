<?php
// database/migrations/2024_01_01_000007_create_payments_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->string('payment_number')->unique(); // nomor pembayaran
            
            $table->enum('payment_method', [
                'bank_transfer',
                'e_wallet',
                'cod', // cash on delivery
                'other'
            ])->default('bank_transfer');
            
            $table->string('bank_name')->nullable(); // nama bank (BCA, Mandiri, dll)
            $table->string('account_number')->nullable(); // nomor rekening
            $table->string('account_name')->nullable(); // nama pemilik rekening
            
            $table->decimal('amount', 15, 2); // jumlah yang dibayar
            $table->string('proof_image')->nullable(); // path bukti transfer
            
            $table->enum('status', [
                'pending',   // menunggu upload bukti
                'uploaded',  // bukti sudah diupload
                'verified',  // sudah diverifikasi
                'rejected'   // ditolak
            ])->default('pending');
            
            $table->text('notes')->nullable(); // catatan dari customer
            $table->text('rejection_reason')->nullable(); // alasan penolakan
            
            $table->foreignId('verified_by')->nullable()->constrained('users'); // admin yang verifikasi
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('uploaded_at')->nullable();
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};