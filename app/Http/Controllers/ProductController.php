<?php

namespace App\Http\Controllers;

use App\Repositories\ProductRepository;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * ProductController — hanya bertugas menerima request, memanggil ProductService,
 * dan mengembalikan response. Tidak ada logika bisnis atau query database di sini.
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService    $service,
        private readonly ProductRepository $repository
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    /**
     * Tampilkan daftar produk dengan pencarian dan filter kategori.
     */
    public function index(Request $request)
    {
        $search     = $request->filled('search') ? $request->input('search') : null;
        $categoryId = $request->filled('category_id') ? (int) $request->input('category_id') : null;

        $totalProducts = $this->repository->countFiltered($search, $categoryId);
        $products      = $this->repository->paginate($search, $categoryId, 100)->appends($request->query());
        $categories    = $this->repository->allCategories();
        $suppliers     = $this->repository->allSuppliers();

        return view('pages.produk.index', compact('products', 'totalProducts', 'categories', 'suppliers'));
    }

    // -------------------------------------------------------------------------
    // Show
    // -------------------------------------------------------------------------

    /**
     * Tampilkan detail satu produk berikut transaksi dan atributnya.
     */
    public function show(int $id)
    {
        $product = $this->repository->findWithRelations($id);
        abort_if($product === null, 404, 'Produk tidak ditemukan.');

        $transactions = \Illuminate\Support\Facades\DB::table('transactions')
            ->leftJoin('users as creator',  'transactions.user_id',    '=', 'creator.id')
            ->leftJoin('users as assignee', 'transactions.assigned_to','=', 'assignee.id')
            ->leftJoin('users as confirmer','transactions.confirmed_by','=', 'confirmer.id')
            ->where('transactions.product_id', $product->id)
            ->select(
                'transactions.*',
                'creator.name  as creator_name',
                'assignee.name as assignee_name',
                'confirmer.name as confirmer_name'
            )
            ->orderByRaw('COALESCE(transactions.confirmed_at, transactions.created_at) desc')
            ->paginate(10);

        $attributes = $this->repository->getAttributes($id);

        return view('pages.produk.show', compact('product', 'transactions', 'attributes'));
    }

    // -------------------------------------------------------------------------
    // Create (form standalone — opsional; modal juga tersedia di index)
    // -------------------------------------------------------------------------

    public function create()
    {
        return view('produk.create', [
            'categories' => $this->repository->allCategories(),
            'suppliers'  => $this->repository->allSuppliers(),
        ]);
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Validasi data produk baru lalu simpan melalui ProductService.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_code'  => 'nullable|string|max:50|unique:products,product_code',
            'name'          => 'required|string|max:255',
            'category_id'   => 'required|integer|exists:categories,id',
            'supplier_id'   => 'required|integer|exists:suppliers,id',
            'description'   => 'nullable|string',
            'purchase_price'=> 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'min_stock'     => 'nullable|integer|min:0',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'attributes'    => 'nullable|array|max:30',
            'attributes.*.name'  => 'nullable|string|max:50',
            'attributes.*.value' => 'nullable|string|max:100',
        ]);

        $this->service->store($validated, $request->file('image'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    // -------------------------------------------------------------------------
    // Export CSV
    // -------------------------------------------------------------------------

    public function export(): StreamedResponse
    {
        return $this->service->exportCsv();
    }

    // -------------------------------------------------------------------------
    // Import CSV
    // -------------------------------------------------------------------------

    public function import(Request $request)
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt', 'max:10240'],
        ]);

        $count = $this->service->importCsv($request->file('file'));

        return redirect()->route('produk.index')->with('success', "{$count} produk berhasil diimpor.");
    }

    // -------------------------------------------------------------------------
    // Edit (JSON — untuk modal)
    // -------------------------------------------------------------------------

    /**
     * Kembalikan data produk sebagai JSON untuk mengisi modal edit di halaman daftar.
     */
    public function edit(int $id)
    {
        $product = $this->repository->findRaw($id);
        abort_if($product === null, 404, 'Produk tidak ditemukan.');

        $product->attributes = $this->repository->getAttributes($id);

        return response()->json($product);
    }

    // -------------------------------------------------------------------------
    // Update
    // -------------------------------------------------------------------------

    public function update(Request $request, int $id)
    {
        $validated = $request->validate([
            'product_code'  => 'nullable|string|max:50|unique:products,product_code,' . $id,
            'name'          => 'required|string|max:255',
            'category_id'   => 'required|integer|exists:categories,id',
            'supplier_id'   => 'required|integer|exists:suppliers,id',
            'description'   => 'nullable|string',
            'purchase_price'=> 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'stock'         => 'required|integer|min:0',
            'min_stock'     => 'nullable|integer|min:0',
            'image'         => 'nullable|image|mimes:jpg,jpeg,png,webp|max:5120',
            'attributes'    => 'nullable|array|max:30',
            'attributes.*.name'  => 'nullable|string|max:50',
            'attributes.*.value' => 'nullable|string|max:100',
        ]);

        $this->service->update($id, $validated, $request->file('image'));

        return redirect()->route('produk.index')->with('success', 'Produk berhasil diperbarui.');
    }

    // -------------------------------------------------------------------------
    // Destroy
    // -------------------------------------------------------------------------

    public function destroy(int $id)
    {
        $this->service->destroy($id);

        return redirect()->route('produk.index')->with('success', 'Produk berhasil dihapus.');
    }

    // -------------------------------------------------------------------------
    // Bulk Destroy
    // -------------------------------------------------------------------------

    public function bulkDestroy(Request $request)
    {
        $request->validate(['ids' => 'required|string']);
        $ids = array_filter(array_map('intval', explode(',', $request->input('ids'))));

        $this->service->bulkDestroy($ids);

        return redirect()->back()->with('success', 'Produk terpilih berhasil dihapus.');
    }
}
