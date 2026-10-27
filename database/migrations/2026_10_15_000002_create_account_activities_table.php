<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Membuat riwayat aktivitas akun untuk mencatat tindakan penting pengguna.
return new class extends Migration
{
    public function up(): void
    {
        // Catat aktivitas penting yang nantinya ditampilkan di halaman profil.
        Schema::create('account_activities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('event', 80);
            $table->string('description');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_activities');
    }
};
