<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menghubungkan transaksi dengan pengguna yang membuat atau mencatatnya.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('transactions', 'user_id')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->foreignId('user_id')->nullable()->after('product_id')->constrained()->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        $columns = Schema::getColumnListing('transactions');
        $userIdPosition = array_search('user_id', $columns, true);
        $productIdPosition = array_search('product_id', $columns, true);

        if ($userIdPosition !== false && $productIdPosition !== false && $userIdPosition > $productIdPosition) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->dropConstrainedForeignId('user_id');
            });
        }
    }
};
