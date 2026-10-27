<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ApplicationSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'app_name',
        'logo_path',
    ];

    // Ambil pengaturan yang tersimpan, atau nilai awal saat tabel belum berisi data.
    public static function current(): self
    {
        return static::query()->first() ?? new static([
            'app_name' => 'Stockify',
            'logo_path' => null,
        ]);
    }
}
