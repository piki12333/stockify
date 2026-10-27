<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    // Skema produk hanya memiliki created_at, sehingga Eloquent tidak boleh mengisi updated_at.
    const UPDATED_AT = null; 

    // Daftar kolom yang boleh diisi secara massal melalui Eloquent.
    protected $fillable = [
        'category_id', 
        'supplier_id', 
        'name', 
        'description', 
        'purchase_price', 
        'selling_price', 
        'stock', 
        'min_stock', 
        'image_url'
    ];
}