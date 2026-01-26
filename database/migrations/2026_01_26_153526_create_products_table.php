<?php
// database/migrations/2024_01_01_000003_create_products_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->onDelete('cascade');
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->text('specifications')->nullable(); // JSON atau text untuk spesifikasi produk
            $table->decimal('price', 15, 2); // harga produk
            $table->integer('stock')->default(0); // stok tersedia
            $table->string('sku')->unique()->nullable(); // Stock Keeping Unit
            $table->string('brand')->nullable(); // merek produk (Samsung, Apple, dll)
            $table->string('image')->nullable(); // path gambar utama
            $table->boolean('is_active')->default(true);
            $table->boolean('is_featured')->default(false); // produk unggulan
            $table->integer('views')->default(0); // jumlah view
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};