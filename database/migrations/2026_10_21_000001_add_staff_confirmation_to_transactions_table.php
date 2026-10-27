<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Menambahkan penugasan dan konfirmasi Staff sebelum stok transaksi diperbarui.
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (! Schema::hasColumn('transactions', 'current_stock')) {
                $table->integer('current_stock')->default(0);
            }
            if (! Schema::hasColumn('transactions', 'status')) {
                $table->string('status', 20)->default('completed');
            }
            if (! Schema::hasColumn('transactions', 'assigned_to')) {
                $table->unsignedInteger('assigned_to')->nullable();
            }
            if (! Schema::hasColumn('transactions', 'confirmed_by')) {
                $table->unsignedInteger('confirmed_by')->nullable();
            }
            if (! Schema::hasColumn('transactions', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable();
            }
        });

        if (! collect(Schema::getIndexes('transactions'))->contains(fn (array $index): bool => $index['name'] === 'transactions_status_index')) {
            Schema::table('transactions', function (Blueprint $table): void {
                $table->index('status');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('transactions')) {
            return;
        }

        Schema::table('transactions', function (Blueprint $table): void {
            if (Schema::hasColumn('transactions', 'status')) {
                $table->dropIndex(['status']);
                $table->dropColumn('status');
            }
            $columns = array_values(array_filter(
                ['assigned_to', 'confirmed_by', 'confirmed_at'],
                static fn (string $column): bool => Schema::hasColumn('transactions', $column)
            ));
            if ($columns !== []) {
                $table->dropColumn($columns);
            }
        });
    }
};
