<?php
// database/migrations/2024_01_01_000006_create_order_items_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            
            $table->string('product_name'); // simpan nama produk saat transaksi
            $table->string('product_sku')->nullable(); // simpan SKU saat transaksi
            $table->decimal('price', 15, 2); // harga per item saat transaksi
            $table->integer('quantity'); // jumlah item
            $table->decimal('subtotal', 15, 2); // price * quantity
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};