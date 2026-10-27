<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockOpnameController extends Controller
{
    // Tampilkan pencatatan stok fisik dengan filter produk dan rentang tanggal.
    public function index(Request $request)
    {
        $query = DB::table('stock_opnames')
            ->join('products', 'stock_opnames.product_id', '=', 'products.id')
            ->leftJoin('users', 'stock_opnames.user_id', '=', 'users.id')
            ->select(
                'stock_opnames.*', 
                'products.name as product_name', 
                'users.name as user_name',
                'reviewers.name as reviewer_name'
            )
            ->leftJoin('users as reviewers', 'stock_opnames.reviewed_by', '=', 'reviewers.id');

        // Filter pencarian berdasarkan Nama Produk atau Catatan
        if ($request->has('search') && $request->search != '') {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('products.name', 'like', "%{$search}%")
                  ->orWhere('stock_opnames.notes', 'like', "%{$search}%");
            });
        }

        // Filter berdasarkan rentang tanggal mulai
        if ($request->has('start_date') && $request->start_date != '') {
            $query->whereDate('stock_opnames.created_at', '>=', $request->start_date);
        }

        // Filter berdasarkan rentang tanggal akhir
        if ($request->has('end_date') && $request->end_date != '') {
            $query->whereDate('stock_opnames.created_at', '<=', $request->end_date);
        }

        $stockOpnames = $query->orderBy('stock_opnames.created_at', 'desc')->get();

        // Mengambil data produk untuk pilihan di form tambah
        $products = DB::table('products')->get();

        return view('pages.stock_opnames.index', compact('stockOpnames', 'products'));
    }

    // Catat hasil hitung fisik untuk ditinjau sebelum mengubah stok sistem.
    public function store(Request $request)
    {
    $request->validate([
        'product_id' => 'required|exists:products,id',
        'physical_stock' => 'required|integer|min:0',
        'notes' => 'nullable|string|max:255',
    ]);

    $product = DB::table('products')->where('id', $request->product_id)->first();
    abort_if(! $product, 404);
    $systemStock = $product->stock;
    $physicalStock = $request->physical_stock;
    $difference = $physicalStock - $systemStock;

    DB::table('stock_opnames')->insert([
        'product_id' => $request->product_id,
        'user_id' => $request->user()->id,
        'system_stock' => $systemStock,
        'physical_stock' => $physicalStock,
        'difference' => $difference,
        'notes' => $request->notes,
        'status' => 'pending',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    return redirect()->route('stock-opnames.index')->with('success', 'Pengajuan stock opname berhasil dikirim dan menunggu persetujuan.');
    }

    // Setujui pengajuan dan terapkan jumlah stok fisik secara atomik.
    public function approve(Request $request, int $stock_opname)
    {
    $validated = $request->validate([
        'review_notes' => 'nullable|string|max:255',
    ]);

    DB::transaction(function () use ($request, $stock_opname, $validated): void {
        $opname = DB::table('stock_opnames')
            ->where('id', $stock_opname)
            ->lockForUpdate()
            ->first();
        abort_if(! $opname, 404);

        abort_if($opname->status !== 'pending', 422, 'Pengajuan ini sudah ditinjau.');
        abort_if((int) $opname->user_id === (int) $request->user()->id, 403, 'Anda tidak dapat menyetujui pengajuan sendiri.');

        $product = DB::table('products')
            ->where('id', $opname->product_id)
            ->lockForUpdate()
            ->first();
        abort_if(! $product, 404);
        $difference = (int) $opname->physical_stock - (int) $product->stock;

        DB::table('products')->where('id', $product->id)->update([
            'stock' => $opname->physical_stock,
            'updated_at' => now(),
        ]);

        DB::table('stock_opnames')->where('id', $opname->id)->update([
            'system_stock' => $product->stock,
            'difference' => $difference,
            'status' => 'approved',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'] ?? null,
            'updated_at' => now(),
        ]);
    });

    return redirect()->route('stock-opnames.index')->with('success', 'Pengajuan disetujui dan stok produk berhasil diperbarui.');
    }

    // Tolak pengajuan dengan alasan agar petugas mengetahui hasil peninjauan.
    public function reject(Request $request, int $stock_opname)
    {
    $validated = $request->validate([
        'review_notes' => 'required|string|max:255',
    ]);

    DB::transaction(function () use ($request, $stock_opname, $validated): void {
        $opname = DB::table('stock_opnames')
            ->where('id', $stock_opname)
            ->lockForUpdate()
            ->first();
        abort_if(! $opname, 404);

        abort_if($opname->status !== 'pending', 422, 'Pengajuan ini sudah ditinjau.');
        abort_if((int) $opname->user_id === (int) $request->user()->id, 403, 'Anda tidak dapat menolak pengajuan sendiri.');

        DB::table('stock_opnames')->where('id', $opname->id)->update([
            'status' => 'rejected',
            'reviewed_by' => $request->user()->id,
            'reviewed_at' => now(),
            'review_notes' => $validated['review_notes'],
            'updated_at' => now(),
        ]);
    });

    return redirect()->route('stock-opnames.index')->with('success', 'Pengajuan stock opname ditolak. Stok produk tidak diubah.');
    }

    // Hapus satu catatan stock opname berdasarkan ID.
    public function destroy($id)
    {
        DB::table('stock_opnames')->where('id', $id)->delete();
        return redirect()->route('stock-opnames.index')->with('success', 'Riwayat stock opname berhasil dihapus!');
    }
}