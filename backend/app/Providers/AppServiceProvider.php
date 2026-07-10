<?php

namespace App\Providers;

use App\Models\Category;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AuditableObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Category::observe(AuditableObserver::class);
        Customer::observe(AuditableObserver::class);
        Product::observe(AuditableObserver::class);
        Sale::observe(AuditableObserver::class);
        Supplier::observe(AuditableObserver::class);
        User::observe(AuditableObserver::class);
    }
}
