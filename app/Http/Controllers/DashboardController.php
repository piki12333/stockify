<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    // Kumpulkan ringkasan inventaris, stok, dan transaksi untuk halaman utama.
    public function index(Request $request)
    {
        $user = $request->user();

        // Ambil statistik utama persediaan dan data yang dipakai kartu dashboard.
        $totalProducts = DB::table('products')->count();
        $totalCategories = DB::table('categories')->count();
        $totalSuppliers = DB::table('suppliers')->count();
        $totalAssetValue = DB::table('products')->sum(DB::raw('stock * purchase_price'));
        $totalStockUnits = DB::table('products')->sum('stock');

        $today = Carbon::today();

        // Hitung jumlah barang masuk dan keluar sepanjang waktu.
        $totalIn = DB::table('transactions')
            ->where('status', 'completed')
            ->whereIn('type', ['in', 'Barang Masuk'])
            ->sum('quantity');

        $totalOut = DB::table('transactions')
            ->where('status', 'completed')
            ->whereIn('type', ['out', 'Barang Keluar'])
            ->sum('quantity');

        // Hitung aktivitas stok yang terjadi pada hari ini.
        $todayIn = DB::table('transactions')
            ->where('status', 'completed')
            ->whereDate(DB::raw('COALESCE(confirmed_at, created_at)'), $today)
            ->whereIn('type', ['in', 'Barang Masuk'])
            ->sum('quantity');

        $todayOut = DB::table('transactions')
            ->where('status', 'completed')
            ->whereDate(DB::raw('COALESCE(confirmed_at, created_at)'), $today)
            ->whereIn('type', ['out', 'Barang Keluar'])
            ->sum('quantity');

        // Ambil jumlah aktivitas per hari untuk grafik tujuh hari terakhir.
        $chartStart = $today->copy()->subDays(6);
        $dailyMovements = DB::table('transactions')
            ->selectRaw('DATE(COALESCE(confirmed_at, created_at)) as activity_date, type, SUM(quantity) as quantity')
            ->where('status', 'completed')
            ->whereRaw('COALESCE(confirmed_at, created_at) >= ?', [$chartStart->startOfDay()])
            ->whereIn('type', ['in', 'out', 'Barang Masuk', 'Barang Keluar'])
            ->groupByRaw('DATE(COALESCE(confirmed_at, created_at)), type')
            ->get()
            ->groupBy('activity_date');

        $stockMovementLabels = [];
        $stockMovementIn = [];
        $stockMovementOut = [];

        for ($day = $chartStart->copy(); $day->lte($today); $day->addDay()) {
            $dateKey = $day->toDateString();
            $stockMovementLabels[] = $day->format('d M');
            $movements = $dailyMovements->get($dateKey, collect());
            $stockMovementIn[] = (int) $movements
                ->whereIn('type', ['in', 'Barang Masuk'])
                ->sum('quantity');
            $stockMovementOut[] = (int) $movements
                ->whereIn('type', ['out', 'Barang Keluar'])
                ->sum('quantity');
        }

        // Tampilkan daftar singkat stok yang sudah menyentuh batas minimum.
        $lowStockCount = DB::table('products')
            ->whereColumn('stock', '<=', 'min_stock')
            ->count();

        $lowStockProducts = DB::table('products')
            ->join('categories', 'products.category_id', '=', 'categories.id')
            ->select('products.*', 'categories.name as category_name')
            ->whereColumn('products.stock', '<=', 'products.min_stock')
            ->orderBy('products.stock', 'asc')
            ->limit(5)
            ->get();

        // Ambil transaksi terbaru untuk daftar aktivitas dashboard.
        $recentTransactions = DB::table('transactions')
            ->join('products', 'transactions.product_id', '=', 'products.id')
            ->leftJoin('suppliers', 'products.supplier_id', '=', 'suppliers.id')
            ->where('transactions.status', 'completed')
            ->select(
                'transactions.*',
                DB::raw('COALESCE(transactions.confirmed_at, transactions.created_at) as activity_at'),
                'products.name as product_name',
                DB::raw("COALESCE(suppliers.name, '-') as supplier_name")
            )
            ->orderByRaw('COALESCE(transactions.confirmed_at, transactions.created_at) desc')
            ->limit(5)
            ->get();

        $assignedTasks = collect();
        $assignedTaskCount = 0;
        if ($user->hasRole(User::ROLE_STAFF)) {
            $assignedTaskCount = DB::table('transactions')
                ->where('assigned_to', $user->id)
                ->where('status', 'pending')
                ->count();
            $assignedTasks = DB::table('transactions')
                ->join('products', 'transactions.product_id', '=', 'products.id')
                ->leftJoin('users as creators', 'transactions.user_id', '=', 'creators.id')
                ->where('transactions.assigned_to', $user->id)
                ->where('transactions.status', 'pending')
                ->select(
                    'transactions.id',
                    'transactions.type',
                    'transactions.quantity',
                    'transactions.notes',
                    'transactions.created_at',
                    'products.name as product_name',
                    'creators.name as creator_name'
                )
                ->orderBy('transactions.created_at')
                ->limit(5)
                ->get();
        }

        $recentAccountActivities = collect();
        if ($user->hasRole(User::ROLE_ADMIN)) {
            $recentAccountActivities = DB::table('account_activities')
                ->join('users', 'account_activities.user_id', '=', 'users.id')
                ->select(
                    'account_activities.event',
                    'account_activities.description',
                    'account_activities.created_at',
                    'users.name as user_name',
                    'users.email as user_email',
                    'users.role as user_role'
                )
                ->latest('account_activities.created_at')
                ->limit(8)
                ->get();
        }

        return view('pages.dashboard.index', compact(
            'user',
            'totalProducts',
            'totalCategories',
            'totalSuppliers',
            'totalAssetValue',
            'totalStockUnits',
            'totalIn',
            'totalOut',
            'todayIn',
            'todayOut',
            'stockMovementLabels',
            'stockMovementIn',
            'stockMovementOut',
            'lowStockCount',
            'lowStockProducts',
            'recentTransactions',
            'assignedTasks',
            'assignedTaskCount',
            'recentAccountActivities'
        ));
    }
}
