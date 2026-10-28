<?php

namespace App\Repositories;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class StockOpnameRepository
{
    /**
     * Ambil data stock opname dengan relasi produk, pencatat, dan peninjau.
     */
    public function getFiltered(?string $search, ?string $startDate, ?string $endDate): Collection
    {
        $query = DB::table('stock_opnames')
            ->join('products', 'stock_opnames.product_id', '=', 'products.id')
            ->leftJoin('users', 'stock_opnames.user_id', '=', 'users.id')
            ->leftJoin('users as reviewers', 'stock_opnames.reviewed_by', '=', 'reviewers.id')
            ->select(
                'stock_opnames.*',
                'products.name as product_name',
                'users.name as user_name',
                'reviewers.name as reviewer_name'
            );

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('stock_opnames.notes', 'like', "%{$search}%");
            });
        }

        if ($startDate !== null && $startDate !== '') {
            $query->whereDate('stock_opnames.created_at', '>=', $startDate);
        }

        if ($endDate !== null && $endDate !== '') {
            $query->whereDate('stock_opnames.created_at', '<=', $endDate);
        }

        return $query->orderBy('stock_opnames.created_at', 'desc')->get();
    }

    /**
     * Ambil seluruh produk untuk pilihan di dropdown form pengajuan.
     */
    public function getAllProducts(): Collection
    {
        return DB::table('products')->orderBy('name')->get();
    }

    /**
     * Cari produk berdasarkan ID.
     */
    public function findProduct(int $productId): ?object
    {
        return DB::table('products')->where('id', $productId)->first();
    }

    /**
     * Cari produk dengan kunci lockForUpdate.
     */
    public function findProductForUpdate(int $productId): ?object
    {
        return DB::table('products')->where('id', $productId)->lockForUpdate()->first();
    }

    /**
     * Simpan pengajuan stock opname baru.
     */
    public function create(array $data): int
    {
        return (int) DB::table('stock_opnames')->insertGetId(
            array_merge($data, [
                'status' => 'pending',
                'created_at' => now(),
                'updated_at' => now(),
            ])
        );
    }

    /**
     * Cari catatan stock opname dengan kunci lockForUpdate.
     */
    public function findForUpdate(int $id): ?object
    {
        return DB::table('stock_opnames')->where('id', $id)->lockForUpdate()->first();
    }

    /**
     * Perbarui jumlah stok di master produk.
     */
    public function updateProductStock(int $productId, int $newStock): void
    {
        DB::table('products')->where('id', $productId)->update([
            'stock' => $newStock,
            'updated_at' => now(),
        ]);
    }

    /**
     * Perbarui status dan data stock opname.
     */
    public function update(int $id, array $data): void
    {
        DB::table('stock_opnames')->where('id', $id)->update(
            array_merge($data, [
                'updated_at' => now(),
            ])
        );
    }

    /**
     * Hapus riwayat stock opname.
     */
    public function delete(int $id): int
    {
        return DB::table('stock_opnames')->where('id', $id)->delete();
    }
}
