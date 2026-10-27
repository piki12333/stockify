<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountActivity extends Model
{
    // Batasi atribut yang dapat diisi saat aktivitas dibuat.
    protected $fillable = [
        'user_id',
        'event',
        'description',
        'ip_address',
        'user_agent',
    ];

    // Setiap catatan aktivitas dimiliki oleh satu pengguna.
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
