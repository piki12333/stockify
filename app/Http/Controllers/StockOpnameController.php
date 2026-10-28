<?php

namespace App\Http\Controllers;

use App\Services\StockOpnameService;
use Illuminate\Http\Request;

class StockOpnameController extends Controller
{
    public function __construct(
        private readonly StockOpnameService $service
    ) {}

    // Tampilkan pencatatan stok fisik dengan filter produk dan rentang tanggal.
    public function index(Request $request)
    {
        $search = $request->input('search');
        $startDate = $request->input('start_date');
        $endDate = $request->input('end_date');

        $stockOpnames = $this->service->getFilteredOpnames($search, $startDate, $endDate);
        $products = $this->service->getAllProducts();

        return view('pages.stock_opnames.index', compact('stockOpnames', 'products'));
    }

    // Catat hasil hitung fisik untuk ditinjau sebelum mengubah stok sistem.
    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'physical_stock' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:255',
        ]);

        $this->service->submitOpname($request->user()->id, $validated);

        return redirect()->route('stock-opnames.index')
            ->with('success', 'Pengajuan stock opname berhasil dikirim dan menunggu persetujuan.');
    }

    // Setujui pengajuan dan terapkan jumlah stok fisik secara atomik.
    public function approve(Request $request, int $stock_opname)
    {
        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:255',
        ]);

        $this->service->approveOpname(
            $request->user()->id,
            $stock_opname,
            $validated['review_notes'] ?? null
        );

        return redirect()->route('stock-opnames.index')
            ->with('success', 'Pengajuan disetujui dan stok produk berhasil diperbarui.');
    }

    // Tolak pengajuan dengan alasan agar petugas mengetahui hasil peninjauan.
    public function reject(Request $request, int $stock_opname)
    {
        $validated = $request->validate([
            'review_notes' => 'required|string|max:255',
        ]);

        $this->service->rejectOpname(
            $request->user()->id,
            $stock_opname,
            $validated['review_notes']
        );

        return redirect()->route('stock-opnames.index')
            ->with('success', 'Pengajuan stock opname ditolak. Stok produk tidak diubah.');
    }

    // Hapus satu catatan stock opname berdasarkan ID.
    public function destroy(int $id)
    {
        $this->service->deleteOpname($id);

        return redirect()->route('stock-opnames.index')
            ->with('success', 'Riwayat stock opname berhasil dihapus!');
    }
}
