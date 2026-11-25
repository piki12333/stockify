<?php

namespace App\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * ProductRepository — seluruh interaksi dengan tabel products, categories,
 * suppliers, dan product_attributes dikumpulkan di sini.
 */
class ProductRepository
{
    // -------------------------------------------------------------------------
    // Listing & Search
    // -------------------------------------------------------------------------

    /**
     * Kembalikan daftar produk yang dapat dipaginasi dengan filter pencarian
     * opsional dan filter kategori.
     */
    public function paginate(
        ?string $search,
        ?int $categoryId,
        int $perPage = 25
    ): LengthAwarePaginator {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->select(
                'products.*',
                'categories.name as category_name',
                'suppliers.name as supplier_name'
            );

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.product_code', 'like', "%{$search}%")
                  ->orWhere('products.description', 'like', "%{$search}%")
                  ->orWhere('categories.name', 'like', "%{$search}%")
                  ->orWhere('suppliers.name', 'like', "%{$search}%");
            });
        }

        if ($categoryId !== null) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->orderByDesc('products.id')->paginate($perPage);
    }

    /**
     * Hitung total produk yang sesuai dengan filter yang diberikan.
     */
    public function countFiltered(?string $search, ?int $categoryId): int
    {
        $query = DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id');

        if ($search !== null && $search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('products.product_code', 'like', "%{$search}%")
                  ->orWhere('products.description', 'like', "%{$search}%")
                  ->orWhere('categories.name', 'like', "%{$search}%")
                  ->orWhere('suppliers.name', 'like', "%{$search}%");
            });
        }

        if ($categoryId !== null) {
            $query->where('products.category_id', $categoryId);
        }

        return $query->count('products.id');
    }

    // -------------------------------------------------------------------------
    // Find / Single
    // -------------------------------------------------------------------------

    /**
     * Ambil satu produk berikut nama kategori dan supplier-nya.
     * Kembalikan null jika tidak ditemukan.
     */
    public function findWithRelations(int $id): ?object
    {
        return DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('products.id', $id)
            ->select(
                'products.*',
                'categories.name as category_name',
                'suppliers.name as supplier_name'
            )
            ->first();
    }

    /**
     * Ambil data produk mentah untuk kebutuhan form edit.
     */
    public function findRaw(int $id): ?object
    {
        return DB::table('products')->where('id', $id)->first();
    }

    // -------------------------------------------------------------------------
    // Create & Update
    // -------------------------------------------------------------------------

    /**
     * Sisipkan produk baru ke database dan kembalikan ID-nya.
     */
    public function create(array $data): int
    {
        return (int) DB::table('products')->insertGetId(
            array_merge($data, ['created_at' => now(), 'updated_at' => now()])
        );
    }

    /**
     * Perbarui data produk yang ada.
     */
    public function update(int $id, array $data): void
    {
        DB::table('products')
            ->where('id', $id)
            ->update(array_merge($data, ['updated_at' => now()]));
    }

    // -------------------------------------------------------------------------
    // Delete
    // -------------------------------------------------------------------------

    /**
     * Hapus satu produk berdasarkan ID.
     */
    public function delete(int $id): void
    {
        DB::table('products')->where('id', $id)->delete();
    }

    /**
     * Hapus banyak produk sekaligus.
     *
     * @param  int[]  $ids
     */
    public function deleteMany(array $ids): void
    {
        DB::table('products')->whereIn('id', $ids)->delete();
    }

    /**
     * Ambil koleksi produk berdasarkan beberapa ID (untuk kebutuhan penghapusan gambar).
     *
     * @param  int[]  $ids
     */
    public function findManyRaw(array $ids): Collection
    {
        return DB::table('products')->whereIn('id', $ids)->get();
    }

    // -------------------------------------------------------------------------
    // Attributes
    // -------------------------------------------------------------------------

    /**
     * Ambil semua atribut untuk satu produk.
     */
    public function getAttributes(int $productId): Collection
    {
        return DB::table('product_attributes')
            ->where('product_id', $productId)
            ->orderBy('id')
            ->get(['attribute_name as name', 'attribute_value as value']);
    }

    /**
     * Simpan atribut produk (tidak menghapus yang lama — lakukan delete lebih dulu jika perlu).
     *
     * @param  array<int, array{name: string, value: string}>  $attributes
     */
    public function saveAttributes(int $productId, array $attributes): void
    {
        if ($attributes === []) {
            return;
        }

        DB::table('product_attributes')->insert(array_map(
            static fn (array $attr): array => [
                'product_id'      => $productId,
                'attribute_name'  => $attr['name'],
                'attribute_value' => $attr['value'],
            ],
            $attributes
        ));
    }

    /**
     * Hapus semua atribut milik satu produk.
     */
    public function deleteAttributes(int $productId): void
    {
        DB::table('product_attributes')->where('product_id', $productId)->delete();
    }

    /**
     * Hapus atribut milik banyak produk sekaligus.
     *
     * @param  int[]  $productIds
     */
    public function deleteAttributesForMany(array $productIds): void
    {
        DB::table('product_attributes')->whereIn('product_id', $productIds)->delete();
    }

    // -------------------------------------------------------------------------
    // Lookup helpers (untuk dropdown)
    // -------------------------------------------------------------------------

    public function allCategories(): Collection
    {
        return DB::table('categories')->orderBy('name')->get();
    }

    public function allSuppliers(): Collection
    {
        $suppliers = DB::table('suppliers')->orderBy('name')->get();
        $categoryIdsBySupplier = DB::table('supplier_categories')
            ->get(['supplier_id', 'category_id'])
            ->groupBy('supplier_id');

        return $suppliers->each(function (object $supplier) use ($categoryIdsBySupplier): void {
            $supplier->category_ids = $categoryIdsBySupplier
                ->get($supplier->id, collect())
                ->pluck('category_id')
                ->map(static fn ($categoryId): int => (int) $categoryId)
                ->all();
        });
    }

    /**
     * Ambil map nama→ID untuk kategori (dipakai saat import CSV).
     */
    public function categoryNameMap(): \Illuminate\Support\Collection
    {
        return DB::table('categories')
            ->pluck('id', 'name')
            ->mapWithKeys(static fn ($id, $name): array => [strtolower(trim($name)) => $id]);
    }

    /**
     * Ambil map nama→ID untuk supplier (dipakai saat import CSV).
     */
    public function supplierNameMap(): \Illuminate\Support\Collection
    {
        return DB::table('suppliers')
            ->pluck('id', 'name')
            ->mapWithKeys(static fn ($id, $name): array => [strtolower(trim($name)) => $id]);
    }

    // -------------------------------------------------------------------------
    // Export helpers
    // -------------------------------------------------------------------------

    /**
     * Kembalikan cursor produk dengan relasi kategori & supplier untuk ekspor CSV.
     */
    public function cursorForExport(): \Illuminate\Support\LazyCollection
    {
        return DB::table('products')
            ->leftJoin('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->select(
                'products.id',
                'products.product_code',
                'products.name',
                'categories.name as category_name',
                'suppliers.name as supplier_name',
                'products.description',
                'products.purchase_price',
                'products.selling_price',
                'products.stock',
                'products.min_stock',
                'products.image_url'
            )
            ->orderBy('products.id')
            ->cursor();
    }

    /**
     * Ambil seluruh atribut terkelompok berdasarkan product_id (untuk ekspor CSV).
     */
    public function allAttributesGrouped(): \Illuminate\Support\Collection
    {
        return DB::table('product_attributes')
            ->orderBy('product_id')
            ->orderBy('id')
            ->get(['product_id', 'attribute_name', 'attribute_value'])
            ->groupBy('product_id');
    }

    // -------------------------------------------------------------------------
    // Low-stock helper (untuk dashboard)
    // -------------------------------------------------------------------------

    /**
     * Ambil produk yang stoknya sudah mencapai atau di bawah batas minimum.
     */
    public function lowStockProducts(): Collection
    {
        return DB::table('products')
            ->whereColumn('stock', '<=', 'min_stock')
            ->select('id', 'name', 'stock', 'min_stock')
            ->orderBy('stock')
            ->get();
    }
}
