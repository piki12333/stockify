<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

// Menyamakan nilai role lama dengan kode role yang digunakan aplikasi.
return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` VARCHAR(32) NOT NULL DEFAULT 'warehouse_staff'");
        }

        DB::table('users')->where('role', 'Admin')->update(['role' => 'admin']);
        DB::table('users')->where('role', 'Manajer Gudang')->update(['role' => 'warehouse_manager']);
        DB::table('users')->where('role', 'Staff Gudang')->update(['role' => 'warehouse_staff']);
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'mysql') {
            DB::statement("ALTER TABLE `users` MODIFY `role` ENUM('Admin', 'Manajer Gudang', 'Staff Gudang') NOT NULL");
        }

        DB::table('users')->where('role', 'admin')->update(['role' => 'Admin']);
        DB::table('users')->where('role', 'warehouse_manager')->update(['role' => 'Manajer Gudang']);
        DB::table('users')->where('role', 'warehouse_staff')->update(['role' => 'Staff Gudang']);
    }
};
