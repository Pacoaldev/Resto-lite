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
            function ($app) {
                $country = strtolower(env('TAX_COUNTRY', 'es'));

                return match ($country) {
                    'mx', 'mexico' => new \App\Orders\Infrastructure\Tax\MexicoTaxCalculator(),
                    'ar', 'argentina' => new \App\Orders\Infrastructure\Tax\ArgentinaTaxCalculator(),
                    'cl', 'chile' => new \App\Orders\Infrastructure\Tax\ChileTaxCalculator(),
                    'co', 'colombia' => new \App\Orders\Infrastructure\Tax\ColombiaTaxCalculator(),
                    'pe', 'peru' => new \App\Orders\Infrastructure\Tax\PeruTaxCalculator(),
                    default => new \App\Orders\Infrastructure\Tax\SpainTaxCalculator(),
                };
            }
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
