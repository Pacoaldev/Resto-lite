<?php

use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Domain\InventoryRepositoryInterface;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Infrastructure\Persistence\EloquentInventoryRepository;
use App\Orders\Infrastructure\Persistence\EloquentOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
    $this->app->bind(InventoryRepositoryInterface::class, EloquentInventoryRepository::class);
    Event::fake();

    DB::table('tables')->insert([
        'id' => 1,
        'name' => 'Mesa 1',
        'status' => 'free',
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

it('GET /api/inventory lista stock', function () {
    $this->getJson('/api/inventory')
        ->assertOk()
        ->assertJsonPath('0.name', 'Cafe')
        ->assertJsonPath('0.stock', 10);
});

it('POST /api/inventory/waste descuenta stock y registra movimiento', function () {
    $this->postJson('/api/inventory/waste', [
        'productId' => 1,
        'quantity' => 3,
        'reason' => 'rotura',
    ])
        ->assertOk()
        ->assertJsonPath('productId', 1)
        ->assertJsonPath('stock', 7);

    expect((int) DB::table('products')->where('id', 1)->value('stock'))->toBe(7)
        ->and(DB::table('stock_movements')->where('type', 'waste')->count())->toBe(1)
        ->and((int) DB::table('stock_movements')->where('type', 'waste')->value('quantity'))->toBe(-3);
});

it('POST /api/inventory/waste devuelve 409 sin stock', function () {
    $this->postJson('/api/inventory/waste', [
        'productId' => 1,
        'quantity' => 99,
    ])
        ->assertStatus(409);
});

it('crear pedido registra movimiento sale', function () {
    app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 2],
    ]);

    expect(DB::table('stock_movements')->where('type', 'sale')->count())->toBe(1)
        ->and((int) DB::table('stock_movements')->where('type', 'sale')->value('quantity'))->toBe(-2)
        ->and((int) DB::table('products')->where('id', 1)->value('stock'))->toBe(8);
});

it('GET /api/inventory/movements lista recientes', function () {
    $this->postJson('/api/inventory/waste', [
        'productId' => 1,
        'quantity' => 1,
        'reason' => 'caducado',
    ])->assertOk();

    $this->getJson('/api/inventory/movements?limit=5')
        ->assertOk()
        ->assertJsonPath('0.type', 'waste')
        ->assertJsonPath('0.productName', 'Cafe');
});
