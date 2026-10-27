<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    // Kolom yang boleh diisi lewat operasi pembuatan atau pembaruan massal.
    // Supaya form bisa menyimpan nama dan deskripsi kategori
    protected $fillable = [
        'name',
        'description',
    ];

    // Satu kategori dapat digunakan oleh banyak produk.
    /**
     * Relasi ke model Product (Satu kategori memiliki banyak produk)
     */
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}