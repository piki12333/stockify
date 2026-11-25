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
        $suppliers = DB::table('suppliers')->orderBy('id', 'desc')->get();
        $categoriesBySupplier = DB::table('supplier_categories')
            ->join('categories', 'supplier_categories.category_id', '=', 'categories.id')
            ->get(['supplier_categories.supplier_id', 'categories.id', 'categories.name'])
            ->groupBy('supplier_id');

        return $suppliers->each(function (object $supplier) use ($categoriesBySupplier): void {
            $categories = $categoriesBySupplier->get($supplier->id, collect());
            $supplier->category_ids = $categories->pluck('id')->map(static fn ($id): int => (int) $id)->all();
            $supplier->category_names = $categories->pluck('name')->all();
        });
    }

    public function getCategories(): Collection
    {
        return DB::table('categories')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Cari supplier berdasarkan ID.
     */
    public function findById(int $id): ?object
    {
        $supplier = DB::table('suppliers')->where('id', $id)->first();
        if ($supplier === null) {
            return null;
        }

        $supplier->category_ids = DB::table('supplier_categories')
            ->where('supplier_id', $id)
            ->pluck('category_id')
            ->map(static fn ($categoryId): int => (int) $categoryId)
            ->all();

        return $supplier;
    }

    /**
     * Buat supplier baru.
     */
    public function create(array $data): int
    {
        return DB::transaction(function () use ($data): int {
            $categoryIds = $data['category_ids'] ?? [];
            unset($data['category_ids']);
            $supplierId = (int) DB::table('suppliers')->insertGetId(array_merge($data, [
                'created_at' => now(),
                'updated_at' => now(),
            ]));

            $this->syncCategories($supplierId, $categoryIds);

            return $supplierId;
        });
    }

    /**
     * Perbarui data supplier.
     */
    public function update(int $id, array $data): int
    {
        return DB::transaction(function () use ($id, $data): int {
            $supplier = DB::table('suppliers')->where('id', $id)->lockForUpdate()->first();
            abort_if($supplier === null, 404);

            $categoryIds = $data['category_ids'] ?? [];
            unset($data['category_ids']);
            DB::table('suppliers')->where('id', $id)->update(array_merge($data, [
                'updated_at' => now(),
            ]));

            $this->syncCategories($id, $categoryIds);

            return $id;
        });
    }

    /**
     * Hapus data supplier.
     */
    public function delete(int $id): bool
    {
        return DB::transaction(function () use ($id): bool {
            $supplier = DB::table('suppliers')->where('id', $id)->lockForUpdate()->first();
            abort_if($supplier === null, 404);

            if (DB::table('products')->where('supplier_id', $id)->exists()) {
                return false;
            }

            DB::table('supplier_categories')->where('supplier_id', $id)->delete();
            DB::table('suppliers')->where('id', $id)->delete();

            return true;
        });
    }

    private function syncCategories(int $supplierId, array $categoryIds): void
    {
        DB::table('supplier_categories')->where('supplier_id', $supplierId)->delete();

        $categoryIds = array_values(array_unique(array_map('intval', $categoryIds)));
        if ($categoryIds === []) {
            return;
        }

        DB::table('supplier_categories')->insert(array_map(
            static fn (int $categoryId): array => [
                'supplier_id' => $supplierId,
                'category_id' => $categoryId,
            ],
            $categoryIds
        ));
    }
}
