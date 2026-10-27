<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menyediakan atribut nama-nilai yang dapat disesuaikan untuk tiap produk.
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('product_attributes')) {
            return;
        }

        Schema::create('product_attributes', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('product_id')->index();
            $table->string('attribute_name', 50);
            $table->string('attribute_value', 100);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_attributes');
    }
};
