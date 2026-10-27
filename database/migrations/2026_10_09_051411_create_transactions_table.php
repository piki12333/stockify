<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Membuat tabel untuk mencatat pergerakan barang masuk dan keluar.
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Simpan barang masuk/keluar dan tautkan setiap catatan ke produk.
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('cascade');
            $table->enum('type', ['in', 'out']); // 'in' untuk barang masuk, 'out' untuk barang keluar
            $table->integer('quantity');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transactions');
    }
};