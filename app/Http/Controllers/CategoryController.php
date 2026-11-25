<?php

namespace App\Http\Controllers;

use App\Services\CategoryService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function __construct(
        private readonly CategoryService $service
    ) {}

    // 1. Menampilkan halaman daftar kategori beserta jumlah produknya (dengan fitur pencarian)
    public function index(Request $request)
    {
        $search = $request->input('search');
        $categories = $this->service->getPaginatedCategories($search, 10);

        return view('pages.categories.index', compact('categories', 'search'));
    }

    // 2. Menyimpan data kategori baru ke database
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $this->service->createCategory($validated);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil ditambahkan!');
    }

    // 3. Memperbarui data kategori
    public function update(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string'
        ]);

        $this->service->updateCategory((int) $id, $validated);

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil diperbarui!');
    }

    // Tolak penghapusan jika kategori masih dipakai produk.
    public function destroy($id)
    {
        $productCount = $this->service->deleteCategory((int) $id);

        if ($productCount > 0) {
            return redirect()->route('categories.index')->with(
                'error',
                "Kategori tidak bisa dihapus karena masih digunakan oleh {$productCount} produk. Pindahkan atau hapus produk tersebut terlebih dahulu."
            );
        }

        return redirect()->route('categories.index')->with('success', 'Kategori berhasil dihapus!');
    }
}
