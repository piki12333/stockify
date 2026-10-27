<?php

namespace App\Repositories;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * TransactionRepository — seluruh query terkait tabel transactions dikumpulkan di sini.
 */
class TransactionRepository
{
    /**
     * Kembalikan daftar transaksi yang dapat dipaginasi dengan filter pencarian,
     * jenis transaksi, dan rentang tanggal.
     */
    public function paginate(
        ?string $search,
        ?string $type,
        ?string $startDate,
        ?string $endDate,
        int $perPage = 10
    ): LengthAwarePaginator {
        $query = Transaction::with(['product', 'user', 'assignedTo', 'confirmer']);

        if ($search !== null && $search !== '') {
            $query->whereHas('product', function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('product_code', 'like', "%{$search}%");
            });
        }

        if ($type !== null && $type !== '') {
            $query->where('type', $type);
        }

        if ($startDate !== null && $endDate !== null) {
            $query->whereBetween(
                \Illuminate\Support\Facades\DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'),
                ["{$startDate} 00:00:00", "{$endDate} 23:59:59"]
            );
        } elseif ($startDate !== null) {
            $query->where(
                \Illuminate\Support\Facades\DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'),
                '>=',
                "{$startDate} 00:00:00"
            );
        } elseif ($endDate !== null) {
            $query->where(
                \Illuminate\Support\Facades\DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'),
                '<=',
                "{$endDate} 23:59:59"
            );
        }

        return $query
            ->orderByRaw('COALESCE(transactions.confirmed_at, transactions.created_at) desc')
            ->paginate($perPage);
    }

    /**
     * Hitung total keseluruhan transaksi (tidak terfilter).
     */
    public function totalCount(): int
    {
        return Transaction::count();
    }

    /**
     * Ambil jumlah tugas pending yang ditetapkan kepada staff tertentu.
     */
    public function pendingCountForStaff(int $userId): int
    {
        return Transaction::query()
            ->where('assigned_to', $userId)
            ->where('status', 'pending')
            ->count();
    }

    /**
     * Ambil daftar staff (role = warehouse_staff) untuk dropdown penugasan.
     */
    public function allStaffMembers(): Collection
    {
        return User::query()
            ->where('role', User::ROLE_STAFF)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    /**
     * Buat record transaksi baru.
     */
    public function create(array $data): Transaction
    {
        return Transaction::create($data);
    }

    /**
     * Cari transaksi dengan lock untuk mencegah race condition saat konfirmasi.
     */
    public function findForUpdate(int $id): Transaction
    {
        return Transaction::query()->lockForUpdate()->findOrFail($id);
    }

    /**
     * Perbarui status dan field konfirmasi transaksi.
     */
    public function confirm(Transaction $transaction, int $confirmedBy, int $currentStock): void
    {
        $transaction->update([
            'status'        => 'completed',
            'confirmed_by'  => $confirmedBy,
            'confirmed_at'  => now(),
            'current_stock' => $currentStock,
        ]);
    }
}

