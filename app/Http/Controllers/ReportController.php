<?php

namespace App\Http\Controllers;

use Illuminate\Database\Query\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    // Siapkan laporan, ringkasan, dan daftar kategori untuk filter halaman.
    public function index(Request $request)
    {
        $filters = $this->validatedFilters($request);
        abort_if(
            $filters['report'] === 'activities'
                && ! $request->user()->hasRole('admin', 'warehouse_manager'),
            403
        );
        $categories = DB::table('categories')->orderBy('name')->get(['id', 'name']);
        $report = $filters['report'];
        $data = match ($report) {
            'transactions' => $this->transactionsQuery($filters)->paginate(25)->withQueryString(),
            'activities' => $this->activitiesQuery($filters)->paginate(25)->withQueryString(),
            default => $this->stockRows($filters)->paginate(25)->withQueryString(),
        };

        $summary = $report === 'transactions'
            ? $this->transactionSummary($filters)
            : ($report === 'stock' ? $this->stockSummary($filters) : null);

        return view('pages.reports.index', compact('filters', 'categories', 'report', 'data', 'summary'));
    }

    // Ekspor jenis laporan yang dipilih ke CSV tanpa memuat semua baris sekaligus.
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->validatedFilters($request);
        abort_if(
            $filters['report'] === 'activities'
                && ! $request->user()->hasRole('admin', 'warehouse_manager'),
            403
        );
        $report = $filters['report'];
        $rows = match ($report) {
            'transactions' => $this->transactionsQuery($filters)->cursor(),
            'activities' => $this->activitiesQuery($filters)->cursor(),
            default => $this->stockRows($filters)->cursor(),
        };

        $headers = match ($report) {
            'transactions' => ['Tanggal', 'Kode Produk', 'Produk', 'Kategori', 'Jenis', 'Jumlah', 'Dibuat oleh', 'Email pembuat', 'Dikonfirmasi oleh', 'Catatan'],
            'activities' => ['Tanggal', 'Pengguna', 'Email', 'Aktivitas', 'Deskripsi', 'Alamat IP'],
            default => ['Kode Produk', 'Produk', 'Kategori', 'Stok Awal', 'Barang Masuk', 'Barang Keluar', 'Stok Akhir', 'Batas Minimum'],
        };
        $filename = sprintf('laporan-%s-%s.csv', $report, now()->format('Ymd-His'));

        return response()->streamDownload(function () use ($rows, $headers, $report): void {
            $stream = fopen('php://output', 'w');
            if ($stream === false) {
                throw new \RuntimeException('Gagal membuka keluaran CSV laporan.');
            }

            fwrite($stream, "\xEF\xBB\xBF");
            fputcsv($stream, $headers);
            foreach ($rows as $row) {
                $values = match ($report) {
                    'transactions' => [
                        $row->created_at,
                        $row->product_code,
                        $row->product_name,
                        $row->category_name,
                        $this->transactionTypeLabel($row->type),
                        $row->quantity,
                        $row->user_name ?? 'Pengguna tidak diketahui',
                        $row->user_email ?? '-',
                        $row->confirmer_name ?? '-',
                        $row->notes,
                    ],
                    'activities' => [
                        $row->created_at,
                        $row->user_name ?? 'Pengguna dihapus',
                        $row->user_email ?? '-',
                        $row->event,
                        $row->description,
                        $row->ip_address,
                    ],
                    default => [
                        $row->product_code,
                        $row->product_name,
                        $row->category_name,
                        $row->opening_stock,
                        $row->stock_in,
                        $row->stock_out,
                        $row->closing_stock,
                        $row->min_stock,
                    ],
                };
                fputcsv($stream, array_map($this->safeCsvValue(...), $values));
            }

            fclose($stream);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    // Pastikan filter valid dan gunakan periode bulan ini jika tanggal tidak dipilih.
    private function validatedFilters(Request $request): array
    {
        $validated = $request->validate([
            'report' => ['nullable', Rule::in(['stock', 'transactions', 'activities'])],
            'start_date' => ['nullable', 'date_format:Y-m-d'],
            'end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:start_date'],
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'type' => ['nullable', Rule::in(['in', 'out'])],
            'search' => ['nullable', 'string', 'max:100'],
        ]);

        return [
            'report' => $validated['report'] ?? 'stock',
            'start_date' => $validated['start_date'] ?? now()->startOfMonth()->toDateString(),
            'end_date' => $validated['end_date'] ?? now()->toDateString(),
            'category_id' => $validated['category_id'] ?? null,
            'type' => $validated['type'] ?? null,
            'search' => trim($validated['search'] ?? ''),
        ];
    }

    // Hitung stok awal dan akhir dari pergerakan yang sudah dikonfirmasi dalam periode.
    private function stockRows(array $filters): Builder
    {
        $start = $filters['start_date'] . ' 00:00:00';
        $end = $filters['end_date'] . ' 23:59:59';
        $movementDate = DB::raw('COALESCE(confirmed_at, created_at)');
        $periodMovements = DB::table('transactions')
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN type IN ('in', 'Barang Masuk') THEN quantity ELSE 0 END) as stock_in")
            ->selectRaw("SUM(CASE WHEN type IN ('out', 'Barang Keluar') THEN quantity ELSE 0 END) as stock_out")
            ->where('status', 'completed')
            ->whereBetween($movementDate, [$start, $end])
            ->groupBy('product_id');
        $futureMovements = DB::table('transactions')
            ->select('product_id')
            ->selectRaw("SUM(CASE WHEN type IN ('in', 'Barang Masuk') THEN quantity ELSE -quantity END) as net_movement")
            ->where('status', 'completed')
            ->where($movementDate, '>', $end)
            ->groupBy('product_id');

        return DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoinSub($periodMovements, 'period_movements', 'period_movements.product_id', '=', 'products.id')
            ->leftJoinSub($futureMovements, 'future_movements', 'future_movements.product_id', '=', 'products.id')
            ->select(
                'products.id',
                'products.product_code',
                'products.name as product_name',
                'products.min_stock',
                'categories.name as category_name'
            )
            ->selectRaw('products.stock - COALESCE(future_movements.net_movement, 0) as closing_stock')
            ->selectRaw('COALESCE(period_movements.stock_in, 0) as stock_in')
            ->selectRaw('COALESCE(period_movements.stock_out, 0) as stock_out')
            ->selectRaw('products.stock - COALESCE(future_movements.net_movement, 0) - COALESCE(period_movements.stock_in, 0) + COALESCE(period_movements.stock_out, 0) as opening_stock')
            ->where('products.created_at', '<=', $end)
            ->when($filters['category_id'], fn (Builder $query, string $categoryId) => $query->where('products.category_id', $categoryId))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $search) use ($filters): void {
                    $search->where('products.name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('products.product_code', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->orderBy('categories.name')
            ->orderBy('products.name');
    }

    // Ambil riwayat transaksi selesai beserta produk dan pengguna terkait.
    private function transactionsQuery(array $filters): Builder
    {
        return DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->leftJoin('users', 'transactions.user_id', '=', 'users.id')
            ->leftJoin('users as confirmer', 'transactions.confirmed_by', '=', 'confirmer.id')
            ->select(
                'transactions.*',
                DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at) as activity_at'),
                'products.product_code',
                'products.name as product_name',
                'categories.name as category_name',
                'users.name as user_name',
                'users.email as user_email',
                'confirmer.name as confirmer_name'
            )
            ->whereBetween(DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'), [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59',
            ])
            ->where('transactions.status', 'completed')
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('transactions.type', $type))
            ->when($filters['category_id'], fn (Builder $query, string $categoryId) => $query->where('products.category_id', $categoryId))
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $search) use ($filters): void {
                    $search->where('products.name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('products.product_code', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->orderByDesc(DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'));
    }

    // Ambil jejak aktivitas akun untuk rentang tanggal yang diminta.
    private function activitiesQuery(array $filters): Builder
    {
        return DB::table('account_activities')
            ->leftJoin('users', 'account_activities.user_id', '=', 'users.id')
            ->select(
                'account_activities.*',
                'users.name as user_name',
                'users.email as user_email'
            )
            ->whereBetween('account_activities.created_at', [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59',
            ])
            ->when($filters['search'] !== '', function (Builder $query) use ($filters): void {
                $query->where(function (Builder $search) use ($filters): void {
                    $search->where('users.name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('users.email', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('account_activities.description', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->orderByDesc('account_activities.created_at');
    }

    // Hitung jumlah transaksi serta total unit masuk dan keluar.
    private function transactionSummary(array $filters): object
    {
        $summary = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->whereBetween(DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at)'), [
                $filters['start_date'] . ' 00:00:00',
                $filters['end_date'] . ' 23:59:59',
            ])
            ->where('transactions.status', 'completed')
            ->when($filters['type'], fn (Builder $query, string $type) => $query->where('transactions.type', $type))
            ->when($filters['category_id'], fn (Builder $query, string $categoryId) => $query->where('products.category_id', $categoryId))
            ->selectRaw("COUNT(*) as transaction_count, SUM(CASE WHEN transactions.type IN ('in', 'Barang Masuk') THEN transactions.quantity ELSE 0 END) as stock_in, SUM(CASE WHEN transactions.type IN ('out', 'Barang Keluar') THEN transactions.quantity ELSE 0 END) as stock_out")
            ->first();

        return $summary ?? (object) ['transaction_count' => 0, 'stock_in' => 0, 'stock_out' => 0];
    }

    // Ringkas jumlah produk dan pergerakan stok dari baris laporan yang sama.
    private function stockSummary(array $filters): object
    {
        $totals = DB::query()
            ->fromSub($this->stockRows($filters), 'report_stock')
            ->selectRaw('COUNT(*) as product_count, SUM(closing_stock) as closing_stock, SUM(stock_in) as stock_in, SUM(stock_out) as stock_out')
            ->first();

        return $totals ?? (object) ['product_count' => 0, 'closing_stock' => 0, 'stock_in' => 0, 'stock_out' => 0];
    }

    // Ubah kode transaksi internal menjadi label yang mudah dibaca.
    private function transactionTypeLabel(string $type): string
    {
        return in_array($type, ['in', 'Barang Masuk'], true) ? 'Barang Masuk' : 'Barang Keluar';
    }

    // Awali nilai berbahaya dengan apostrof agar tidak dijalankan sebagai rumus spreadsheet.
    private function safeCsvValue(mixed $value): string
    {
        $value = (string) ($value ?? '');

        return preg_match('/^[\s]*[=+\-@]/u', $value) ? "'" . $value : $value;
    }
}
