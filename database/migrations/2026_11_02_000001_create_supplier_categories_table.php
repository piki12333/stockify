<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_categories', function (Blueprint $table): void {
            $table->increments('id');
            $table->unsignedInteger('supplier_id');
            $table->unsignedInteger('category_id');
            $table->unique(['supplier_id', 'category_id']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_categories');
    }
};
