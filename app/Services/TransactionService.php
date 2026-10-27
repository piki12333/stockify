<?php

namespace App\Services;

use App\Models\AccountActivity;
use App\Models\Product;
use App\Models\Transaction;
use App\Models\User;
use App\Notifications\TransactionRecorded;
use App\Repositories\TransactionRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * TransactionService — logika bisnis transaksi stok:
 * membuat tugas, mengonfirmasi tugas, dan mengirim notifikasi.
 *
 * Semua query diserahkan ke TransactionRepository.
 */
class TransactionService
{
    public function __construct(
        private readonly TransactionRepository $repository
    ) {}

    /**
     * Buat tugas transaksi baru (oleh Admin/Manajer) dan catat aktivitas akun.
     */
    public function createTask(array $validated, Request $request): Transaction
    {
        return DB::transaction(function () use ($validated, $request): Transaction {
            $product = Product::query()->findOrFail($validated['product_id']);

            $transaction = $this->repository->create([
                'product_id'  => $product->id,
                'user_id'     => $request->user()->id,
                'assigned_to' => $validated['assigned_to'],
                'type'        => $validated['type'],
                'quantity'    => $validated['quantity'],
                'status'      => 'pending',
                'notes'       => $validated['notes'] ?? null,
            ]);

            $assignedStaff = User::findOrFail($validated['assigned_to']);

            AccountActivity::create([
                'user_id'     => $request->user()->id,
                'event'       => 'transaction-task-created',
                'description' => sprintf(
                    'Membuat tugas barang %s: %s sebanyak %d unit untuk %s.',
                    $validated['type'] === 'in' ? 'masuk' : 'keluar',
                    $product->name,
                    $validated['quantity'],
                    $assignedStaff->name
                ),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $transaction;
        });
    }

    /**
     * Konfirmasi tugas oleh Staff:
     * - lock baris untuk mencegah race condition
     * - periksa ketersediaan stok (untuk transaksi keluar)
     * - perbarui stok produk
     * - catat aktivitas dan kirim notifikasi
     *
     * Kembalikan Transaction yang sudah dikonfirmasi, atau null jika stok tidak cukup.
     */
    public function confirmTask(int $transactionId, Request $request): ?Transaction
    {
        $confirmedTransaction = DB::transaction(function () use ($transactionId, $request): ?Transaction {
            $task = $this->repository->findForUpdate($transactionId);

            abort_if($task->status !== 'pending', 422, 'Tugas ini sudah dikonfirmasi.');
            abort_if(
                (int) $task->assigned_to !== (int) $request->user()->id,
                403,
                'Tugas ini tidak ditugaskan kepada akun Anda.'
            );

            $product = Product::query()->lockForUpdate()->findOrFail($task->product_id);

            if ($task->type === 'out' && $product->stock < $task->quantity) {
                return null; // stok tidak mencukupi
            }

            $product->stock += $task->type === 'in' ? $task->quantity : -$task->quantity;
            $product->save();

            $this->repository->confirm($task, $request->user()->id, $product->stock);

            AccountActivity::create([
                'user_id'     => $request->user()->id,
                'event'       => 'transaction-' . $task->type,
                'description' => sprintf(
                    'Mengonfirmasi barang %s: %s sebanyak %d unit.',
                    $task->type === 'in' ? 'masuk' : 'keluar',
                    $product->name,
                    $task->quantity
                ),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            return $task->fresh(['product']);
        });

        // Kirim notifikasi ke semua pengguna setelah transaksi berhasil dikonfirmasi.
        if ($confirmedTransaction !== null) {
            User::query()->each(function (User $user) use ($confirmedTransaction): void {
                $user->notify(new TransactionRecorded($confirmedTransaction));
            });
        }

        return $confirmedTransaction;
    }
}

