<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\StockOpnameController;
use App\Http\Controllers\SupplierController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\SettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Pengunjung yang belum login dapat membuka formulir login saja.
    Route::get('/login', [AuthController::class, 'create'])->name('login');
    Route::post('/login', [AuthController::class, 'store'])->middleware('throttle:5,1')->name('login.store');
});

Route::middleware('auth')->group(function () {
    // Lindungi dashboard, halaman profil, dan fitur inventaris dari akses tanpa login.
    // Halaman Utama / Dashboard
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::patch('/profile/avatar', [ProfileController::class, 'updateAvatar'])->name('profile.avatar.update');
    Route::patch('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');
    Route::delete('/profile/sessions/{sessionId}', [ProfileController::class, 'destroySession'])->name('profile.sessions.destroy');
    Route::delete('/profile/sessions', [ProfileController::class, 'destroyOtherSessions'])->name('profile.sessions.destroy-others');
    Route::post('/logout', [AuthController::class, 'destroy'])->name('logout');
    Route::post('/notifications/{notificationId}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');
    Route::middleware('role:admin')->group(function () {
        Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('/settings', [SettingController::class, 'update'])->name('settings.update');
    });
    Route::name('practice.')->group(function () {
        Route::name('first')->get('practice/1', function () {
            return view('pages.practice.1');
        });
        Route::name('second')->get('practice/2', function () {
            return view('pages.practice.2');
        });
    });

    // Seluruh role dapat melihat daftar produk.
    Route::get('/produk', [ProductController::class, 'index'])->name('produk.index');
    Route::get('/produk/{id}', [ProductController::class, 'show'])->whereNumber('id')->name('produk.show');

    // Admin dan manajer dapat menambahkan produk baru.
    Route::middleware('role:admin,warehouse_manager')->group(function () {
        Route::post('/produk/simpan', [ProductController::class, 'store'])->name('produk.store');
    });

    // Perubahan dan penghapusan data produk hanya untuk Admin.
    Route::middleware('role:admin')->group(function () {
        Route::get('/produk/export', [ProductController::class, 'export'])->name('produk.export');
        Route::post('/produk/import', [ProductController::class, 'import'])->name('produk.import');
        Route::get('/produk/{id}/edit', [ProductController::class, 'edit'])->name('produk.edit');
        Route::put('/produk/{id}', [ProductController::class, 'update'])->name('produk.update');
        Route::delete('/produk/{id}', [ProductController::class, 'destroy'])->name('produk.destroy');
        Route::delete('/produk/hapus-banyak', [ProductController::class, 'bulkDestroy'])->name('produk.bulkDestroy');

        Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
        Route::post('/categories', [CategoryController::class, 'store'])->name('categories.store');
        Route::put('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
        Route::delete('/categories/{category}', [CategoryController::class, 'destroy'])->name('categories.destroy');
        Route::post('/suppliers', [SupplierController::class, 'store'])->name('suppliers.store');
        Route::put('/suppliers/{id}', [SupplierController::class, 'update'])->name('suppliers.update');
        Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy'])->name('suppliers.destroy');
        Route::get('/users', [UserController::class, 'index'])->name('users.index');
        Route::post('/users', [UserController::class, 'store'])->name('users.store');
        Route::put('/users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('/users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    // Admin dan manajer dapat melihat laporan operasional serta daftar supplier.
    Route::middleware('role:admin,warehouse_manager')->group(function () {
        Route::get('/reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('/suppliers', [SupplierController::class, 'index'])->name('suppliers.index');
    });

    // Semua role operasional dapat melihat transaksi; Admin/Manajer membuat tugas dan Staff mengonfirmasi tugasnya.
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::middleware('role:admin,warehouse_manager')->group(function () {
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
    });
    Route::post('/transactions/{transaction}/confirm', [TransactionController::class, 'confirm'])
        ->middleware('role:warehouse_staff')
        ->name('transactions.confirm');

    // Semua role dapat mengajukan dan melihat stock opname.
    Route::get('/stock-opnames', [StockOpnameController::class, 'index'])->name('stock-opnames.index');
    Route::post('/stock-opnames', [StockOpnameController::class, 'store'])->name('stock-opnames.store');
    Route::middleware('role:admin,warehouse_manager')->group(function () {
        Route::post('/stock-opnames/{stock_opname}/approve', [StockOpnameController::class, 'approve'])
            ->name('stock-opnames.approve');
        Route::post('/stock-opnames/{stock_opname}/reject', [StockOpnameController::class, 'reject'])
            ->name('stock-opnames.reject');
        Route::delete('/stock-opnames/{stock_opname}', [StockOpnameController::class, 'destroy'])
            ->name('stock-opnames.destroy');
    });
});
