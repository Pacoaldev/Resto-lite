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
            \App\Orders\Domain\TableRepositoryInterface::class,
            \App\Orders\Infrastructure\Persistence\EloquentTableRepository::class
        );
        $this->app->bind(
            \App\Orders\Domain\ProductRepositoryInterface::class,
            \App\Orders\Infrastructure\Persistence\EloquentProductRepository::class
        );
        $this->app->bind(
            \App\Orders\Domain\RecipeRepositoryInterface::class,
            \App\Orders\Infrastructure\Persistence\EloquentRecipeRepository::class
        );
        $this->app->bind(
            \App\Orders\Domain\InventoryRepositoryInterface::class,
            \App\Orders\Infrastructure\Persistence\EloquentInventoryRepository::class
        );
        $this->app->bind(
            \App\Orders\Domain\PaymentGatewayInterface::class,
            \App\Orders\Infrastructure\Payment\FakePaymentGateway::class
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
