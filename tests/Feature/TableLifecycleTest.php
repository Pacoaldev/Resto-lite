<?php

use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Application\RequestBillUseCase;
use App\Orders\Application\SettleTableUseCase;
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
        'id' => 1,
        'name' => 'Mesa 1',
        'status' => 'free',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('products')->insert([
        'id' => 1,
        'name' => 'Tostada',
        'price' => 2.5,
        'stock' => 10,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
});

it('marca la mesa como ocupada al crear un pedido', function () {
    $useCase = app(CreateOrderUseCase::class);

    $useCase->execute(1, [
        ['name' => 'Tostada', 'price' => 2.5, 'quantity' => 1],
    ]);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('occupied');
});

it('genera la cuenta y marca billRequested; al cobrar vuelve a libre', function () {
    app(CreateOrderUseCase::class)->execute(1, [
        ['name' => 'Tostada', 'price' => 2.5, 'quantity' => 2],
    ]);

    $bill = app(RequestBillUseCase::class)->execute(1);

    expect($bill['total'])->toBe(5.0)
        ->and($bill['orderIds'])->not->toBeEmpty()
        ->and(DB::table('tables')->where('id', 1)->value('status'))->toBe('billRequested');

    app(SettleTableUseCase::class)->execute(1);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('free')
        ->and(DB::table('orders')->where('tableId', 1)->value('status'))->toBe('paid');
});

it('libera una mesa billRequested aunque no tenga pedidos abiertos', function () {
    DB::table('tables')->where('id', 1)->update(['status' => 'billRequested']);

    app(SettleTableUseCase::class)->execute(1);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('free');
});
