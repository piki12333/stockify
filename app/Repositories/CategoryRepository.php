<?php

namespace App\Repositories;

use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class CategoryRepository
{
    /**
     * Ambil data kategori dengan pagination dan pencarian.
     */
    public function paginate(?string $search, int $perPage = 10): LengthAwarePaginator
    {
        return Category::withCount('products')
            ->when($search, function ($query, $search) {
                return $query->where('name', 'like', "%{$search}%")
                             ->orWhere('description', 'like', "%{$search}%");
            })
            ->latest()
            ->paginate($perPage)
            ->appends(['search' => $search]);
    }

    /**
     * Buat kategori baru.
     */
    public function create(array $data): Category
    {
        return Category::create($data);
    }

    /**
     * Cari kategori berdasarkan ID.
     */
    public function findById(int $id): Category
    {
        return Category::findOrFail($id);
    }

    /**
     * Perbarui data kategori.
     */
    public function update(int $id, array $data): bool
    {
        $category = $this->findById($id);
        return $category->update($data);
    }

    /**
     * Hapus kategori.
     */
    public function delete(int $id): int
    {
        return DB::transaction(function () use ($id): int {
            $category = Category::query()->lockForUpdate()->findOrFail($id);
            $productCount = $category->products()->count();

            if ($productCount > 0) {
                return $productCount;
            }

            DB::table('supplier_categories')->where('category_id', $id)->delete();
            $category->delete();

            return 0;
        });
    }
}
