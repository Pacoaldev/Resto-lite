<?php

use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Infrastructure\Persistence\EloquentOrderRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
    Event::fake();

    DB::table('tables')->insert([
        ['id' => 1, 'name' => 'Mesa 1', 'status' => 'free', 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Mesa 2', 'status' => 'free', 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('products')->insert([
        ['id' => 1, 'name' => 'Cafe', 'price' => 2.5, 'stock' => 10, 'created_at' => now(), 'updated_at' => now()],
        ['id' => 2, 'name' => 'Tostada', 'price' => 3.0, 'stock' => 5, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('GET /api/tables devuelve el listado con su estado', function () {
    $this->getJson('/api/tables')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', 1)
        ->assertJsonPath('0.status', 'free')
        ->assertJsonPath('1.id', 2);
});

it('GET /api/products devuelve catalogo con stock y precio', function () {
    $this->getJson('/api/products')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.name', 'Cafe')
        ->assertJsonPath('0.price', '2.50')
        ->assertJsonPath('0.stock', 10);
});

it('POST /api/orders crea pedido, ocupa mesa y devuelve total', function () {
    $response = $this->postJson('/api/orders', [
        'tableId' => 1,
        'items' => [
            ['name' => 'Cafe', 'quantity' => 2],
            ['name' => 'Tostada', 'quantity' => 1],
        ],
    ])->assertCreated();

    $response->assertJsonPath('tableId', 1)
        ->assertJsonPath('status', 'open')
        ->assertJsonPath('total', 8);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('occupied');
});

it('POST /api/orders rechaza producto inexistente con 422', function () {
    $this->postJson('/api/orders', [
        'tableId' => 1,
        'items' => [['name' => 'ProductoInexistente', 'quantity' => 1]],
    ])->assertStatus(422)
      ->assertJsonValidationErrors('items.0.name');
});

it('POST /api/orders rechaza mesa inexistente con 422', function () {
    $this->postJson('/api/orders', [
        'tableId' => 999,
        'items' => [['name' => 'Cafe', 'quantity' => 1]],
    ])->assertStatus(422)
      ->assertJsonValidationErrors('tableId');
});

it('POST /api/orders devuelve 409 cuando no hay stock suficiente', function () {
    $this->postJson('/api/orders', [
        'tableId' => 1,
        'items' => [['name' => 'Tostada', 'quantity' => 99]],
    ])->assertStatus(409)
      ->assertJsonPath('message', 'Stock insuficiente para Tostada (disponible: 5)');
});

it('POST /api/orders ignora el precio del cliente y resuelve server-side', function () {
    // Cliente intenta colar precio de 0.01; el servidor usa el de la DB
    $this->postJson('/api/orders', [
        'tableId' => 1,
        'items' => [['name' => 'Cafe', 'price' => 0.01, 'quantity' => 2]],
    ])->assertCreated()
      ->assertJsonPath('total', 5); // 2 * 2.5 del catalogo, no 0.01 (JSON serializa float como int si es entero)
});

it('PATCH /api/orders/{id}/status cambia a sent', function () {
    $order = app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1],
    ]);

    $this->patchJson("/api/orders/{$order->getId()}/status", ['status' => 'sent'])
        ->assertNoContent();

    expect(DB::table('orders')->where('id', $order->getId())->value('status'))->toBe('sent');
});

it('PATCH /api/orders/{id}/status devuelve 404 cuando el pedido no existe', function () {
    $this->patchJson('/api/orders/9999/status', ['status' => 'sent'])
        ->assertNotFound();
});

it('PATCH /api/orders/{id}/status devuelve 422 en estado invalido', function () {
    $order = app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1],
    ]);

    $this->patchJson("/api/orders/{$order->getId()}/status", ['status' => 'patada'])
        ->assertStatus(422)
        ->assertJsonValidationErrors('status');
});

it('GET /api/orders devuelve pedidos recientes ordenados por id desc', function () {
    $a = app(CreateOrderUseCase::class)->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);
    $b = app(CreateOrderUseCase::class)->execute(1, [['name' => 'Tostada', 'price' => 3.0, 'quantity' => 1]]);

    $this->getJson('/api/orders')
        ->assertOk()
        ->assertJsonCount(2)
        ->assertJsonPath('0.id', $b->getId())
        ->assertJsonPath('1.id', $a->getId());
});

it('GET /api/orders respeta el parametro limit', function () {
    for ($i = 0; $i < 5; $i++) {
        app(CreateOrderUseCase::class)->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);
    }

    $this->getJson('/api/orders?limit=3')
        ->assertOk()
        ->assertJsonCount(3);
});