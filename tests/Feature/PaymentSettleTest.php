<?php

use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Application\SettleTableUseCase;
use App\Orders\Domain\InventoryRepositoryInterface;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\PaymentGatewayInterface;
use App\Orders\Domain\PaymentResult;
use App\Orders\Infrastructure\Persistence\EloquentInventoryRepository;
use App\Orders\Infrastructure\Persistence\EloquentOrderRepository;
use App\Orders\Infrastructure\Tax\TaxCountryConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
    $this->app->bind(InventoryRepositoryInterface::class, EloquentInventoryRepository::class);
    Event::fake();
    Cache::forget(TaxCountryConfig::CACHE_KEY);
    TaxCountryConfig::setCountry('es');

    DB::table('tables')->insert([
        'id' => 1,
        'name' => 'Mesa 1',
        'status' => 'occupied',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('products')->insert([
        'id' => 1,
        'name' => 'Cafe',
        'price' => 2.5,
        'stock' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('settle cobra via stub y libera la mesa', function () {
    app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 2],
    ]);

    $result = app(SettleTableUseCase::class)->execute(1);

    expect($result['payment']['provider'])->toBe('stub')
        ->and($result['payment']['status'])->toBe('approved')
        ->and($result['payment']['transactionId'])->toStartWith('pay_')
        ->and($result['orderIds'])->not->toBeEmpty()
        ->and(DB::table('tables')->where('id', 1)->value('status'))->toBe('free');
});

it('POST /api/tables/{id}/settle devuelve 200 con payment', function () {
    app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1],
    ]);

    $this->postJson('/api/tables/1/settle')
        ->assertOk()
        ->assertJsonPath('tableId', 1)
        ->assertJsonPath('payment.provider', 'stub')
        ->assertJsonPath('payment.status', 'approved');
});

it('pago rechazado no libera la mesa', function () {
    app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1],
    ]);

    $this->app->bind(PaymentGatewayInterface::class, fn () => new class implements PaymentGatewayInterface {
        public function charge(int $tableId, float $amount, string $currency, string $country): PaymentResult
        {
            return new PaymentResult('stub', 'declined', 'pay_declined');
        }
    });
    $this->app->forgetInstance(SettleTableUseCase::class);

    expect(fn () => app(SettleTableUseCase::class)->execute(1))
        ->toThrow(DomainException::class, 'Pago rechazado por la pasarela');

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('occupied')
        ->and(DB::table('orders')->where('tableId', 1)->value('status'))->toBe('open');
});
