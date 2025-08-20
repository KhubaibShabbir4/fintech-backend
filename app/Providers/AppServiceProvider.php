<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;
use App\Services\Interfaces\{
    AuthServiceInterface, MerchantServiceInterface, PaymentServiceInterface, TransactionServiceInterface, StatsServiceInterface
};
use App\Services\{ AuthService, MerchantService, PaymentService, TransactionService, StatsService };

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */

    public function register(): void {
        $this->app->bind(AuthServiceInterface::class, AuthService::class);
        $this->app->bind(MerchantServiceInterface::class, MerchantService::class);
        $this->app->bind(PaymentServiceInterface::class, PaymentService::class);
        $this->app->bind(TransactionServiceInterface::class, TransactionService::class);
        $this->app->bind(StatsServiceInterface::class, StatsService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Schema::defaultStringLength(191);
    }
}
