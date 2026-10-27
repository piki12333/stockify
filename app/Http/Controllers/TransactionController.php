<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Models\Product;
use App\Models\User;
use App\Repositories\TransactionRepository;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * TransactionController — hanya menerima request, memanggil TransactionService,
 * dan mengembalikan response. Tidak ada logika bisnis atau query database di sini.
 */
class TransactionController extends Controller
{
    public function __construct(
        private readonly TransactionService    $service,
        private readonly TransactionRepository $repository
    ) {}

    // -------------------------------------------------------------------------
    // Index
    // -------------------------------------------------------------------------

    /**
     * Tampilkan daftar transaksi dengan pencarian, filter jenis, dan filter tanggal.
     */
    public function index(Request $request)
    {
        $search     = $request->has('search') && $request->search != '' ? $request->search : null;
        $type       = $request->filled('type') ? $request->type : null;
        $startDate  = $request->filled('start_date') ? $request->start_date : null;
        $endDate    = $request->filled('end_date') ? $request->end_date : null;

        $transactions      = $this->repository->paginate($search, $type, $startDate, $endDate, 10)->appends($request->all());
        $totalTransactions = $this->repository->totalCount();
        $products          = Product::orderBy('name')->get();
        $assignedTaskCount = 0;

        $staffMembers = $request->user()->hasRole(User::ROLE_ADMIN, User::ROLE_MANAGER)
            ? $this->repository->allStaffMembers()
            : collect();

        if ($request->user()->hasRole(User::ROLE_STAFF)) {
            $assignedTaskCount = $this->repository->pendingCountForStaff($request->user()->id);
        }

        return view('pages.transactions.index', compact(
            'transactions', 'totalTransactions', 'products', 'staffMembers', 'assignedTaskCount'
        ));
    }

    // -------------------------------------------------------------------------
    // Store
    // -------------------------------------------------------------------------

    /**
     * Validasi lalu buat tugas transaksi baru melalui TransactionService.
     */
    public function store(Request $request)
    {
        $request->validate([
            'product_id'  => 'required|exists:products,id',
            'type'        => 'required|in:in,out',
            'quantity'    => 'required|integer|min:1',
            'assigned_to' => [
                'required',
                Rule::exists('users', 'id')->where('role', User::ROLE_STAFF),
            ],
            'notes' => 'nullable|string|max:1000',
        ]);

        $this->service->createTask($request->validated(), $request);

        return redirect()->route('transactions.index')
            ->with('success', 'Tugas transaksi berhasil dibuat. Stok akan berubah setelah Staff mengonfirmasi.');
    }

    // -------------------------------------------------------------------------
    // Confirm
    // -------------------------------------------------------------------------

    /**
     * Konfirmasi tugas oleh Staff melalui TransactionService.
     * Jika stok tidak mencukupi, kembalikan pesan error.
     */
    public function confirm(Request $request, int $transaction)
    {
        $confirmed = $this->service->confirmTask($transaction, $request);

        if ($confirmed === null) {
            $task = Transaction::with('product')->findOrFail($transaction);

            return redirect()->route('transactions.index')->with('error', sprintf(
                'Stok %s tidak mencukupi untuk mengonfirmasi pengeluaran %d unit. Stok tersedia: %d unit.',
                $task->product->name,
                $task->quantity,
                $task->product->stock
            ));
        }

        return redirect()->route('transactions.index')
            ->with('success', 'Tugas berhasil dikonfirmasi dan stok produk telah diperbarui.');
    }
}
