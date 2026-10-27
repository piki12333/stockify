<?php

namespace App\Providers;

use App\Models\ApplicationSetting;
use App\Repositories\CategoryRepository;
use App\Repositories\ProductRepository;
use App\Repositories\SupplierRepository;
use App\Repositories\TransactionRepository;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\SupplierService;
use App\Services\TransactionService;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\View;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Daftarkan Repository sebagai singleton agar instance tidak dibuat ulang
        // setiap kali di-inject ke Service atau Controller yang berbeda.
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(TransactionRepository::class);
        $this->app->singleton(CategoryRepository::class);
        $this->app->singleton(SupplierRepository::class);

        // Service didaftarkan sebagai singleton dan menerima Repository melalui DI.
        $this->app->singleton(ProductService::class, function ($app) {
            return new ProductService($app->make(ProductRepository::class));
        });

        $this->app->singleton(TransactionService::class, function ($app) {
            return new TransactionService($app->make(TransactionRepository::class));
        });

        $this->app->singleton(CategoryService::class, function ($app) {
            return new CategoryService($app->make(CategoryRepository::class));
        });

        $this->app->singleton(SupplierService::class, function ($app) {
            return new SupplierService($app->make(SupplierRepository::class));
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Bagikan nama dan logo yang sama ke halaman login dan kerangka dashboard.
        View::composer([
            'layouts.dashboard',
            'auth.login',
        ], static function ($view): void {
            $view->with('applicationSettings', ApplicationSetting::current());
        });
    }
}
