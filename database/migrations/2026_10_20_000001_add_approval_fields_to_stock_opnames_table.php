<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menyimpan status, pemeriksa, waktu, dan catatan persetujuan stock opname.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            return;
        }

        Schema::table('stock_opnames', function (Blueprint $table): void {
            $table->string('status', 20)->default('approved');
            $table->unsignedInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->string('review_notes')->nullable();
            $table->index('status');
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('stock_opnames')) {
            return;
        }

        Schema::table('stock_opnames', function (Blueprint $table): void {
            $table->dropIndex(['status']);
            $table->dropColumn(['status', 'reviewed_by', 'reviewed_at', 'review_notes']);
        });
    }
};
