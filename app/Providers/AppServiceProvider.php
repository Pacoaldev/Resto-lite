<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(
            \App\Orders\Domain\OrderRepositoryInterface::class,
            \App\Orders\Infrastructure\Persistence\EloquentOrderRepository::class
        );

        $this->app->bind(
            \App\Orders\Domain\TaxCalculatorInterface::class,
            fn () => \App\Orders\Infrastructure\Tax\TaxCountryConfig::calculator()
        );
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
