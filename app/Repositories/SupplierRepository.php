<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class SupplierRepository
{
    /**
     * Ambil semua data supplier.
     */
    public function getAll(): Collection
    {
        return DB::table('suppliers')->orderBy('id', 'desc')->get();
    }

    /**
     * Cari supplier berdasarkan ID.
     */
    public function findById(int $id): ?object
    {
        return DB::table('suppliers')->where('id', $id)->first();
    }

    /**
     * Buat supplier baru.
     */
    public function create(array $data): int
    {
        return (int) DB::table('suppliers')->insertGetId(
            array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );
    }

    /**
     * Perbarui data supplier.
     */
    public function update(int $id, array $data): int
    {
        return DB::table('suppliers')->where('id', $id)->update(
            array_merge($data, [
                'updated_at' => now(),
            ])
        );
    }

    /**
     * Hapus data supplier.
     */
    public function delete(int $id): int
    {
        return DB::table('suppliers')->where('id', $id)->delete();
    }
}

