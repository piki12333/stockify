<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    // Kolom yang diizinkan saat membuat transaksi melalui Eloquent.
    // Tambahkan 'current_stock' di sini supaya Laravel mengizinkan datanya disimpan
    protected $fillable = [
        'product_id',
        'user_id',
        'assigned_to',
        'confirmed_by',
        'type',
        'quantity',
        'current_stock',
        'notes',
        'status',
        'confirmed_at',
    ];

    // Setiap transaksi mengacu pada satu produk.
    // Relasi ke tabel Product
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function assignedTo(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function confirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'confirmed_by');
    }
}