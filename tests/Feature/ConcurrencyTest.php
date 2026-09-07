<?php

use App\Orders\Application\CreateOrderUseCase;
use App\Orders\Application\RequestBillUseCase;
use App\Orders\Application\ChangeOrderStatusUseCase;
use App\Orders\Application\SettleTableUseCase;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\TaxCalculatorInterface;
use App\Orders\Infrastructure\Persistence\EloquentOrderRepository;
use App\Orders\Infrastructure\Tax\TaxCountryConfig;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->app->bind(OrderRepositoryInterface::class, EloquentOrderRepository::class);
    $this->app->bind(TaxCalculatorInterface::class, fn () => TaxCountryConfig::calculator('es'));
    Event::fake();

    DB::table('tables')->insert([
        ['id' => 1, 'name' => 'Mesa 1', 'status' => 'free', 'created_at' => now(), 'updated_at' => now()],
    ]);

    DB::table('products')->insert([
        ['id' => 1, 'name' => 'Cafe', 'price' => 2.5, 'stock' => 100, 'created_at' => now(), 'updated_at' => now()],
    ]);
});

it('dos request-bill concurrentes: solo el primero deja la mesa en billRequested', function () {
    $create = app(CreateOrderUseCase::class);
    $requestBill = app(RequestBillUseCase::class);

    $create->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 2]]);

    // ponytail: lockForUpdate serializa; el segundo encuentra status=billRequested y 422 idempotente
    $first = null;
    $error = null;

    try {
        $first = $requestBill->execute(1);
    } catch (\DomainException $e) {
        $error = $e;
    }

    try {
        $requestBill->execute(1);
    } catch (\DomainException $e) {
        $error = $e;
    }

    expect($first)->not->toBeNull()
        ->and($first['total'])->toBe(6.05)
        ->and($error?->getMessage())->toBe('La cuenta ya fue solicitada para esta mesa');

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('billRequested');
});

it('request-bill en mesa inexistente devuelve mensaje claro', function () {
    $requestBill = app(RequestBillUseCase::class);

    $requestBill->execute(999);
})->throws(\DomainException::class, 'Mesa no encontrada');

it('dos create-order sobre mismo producto: el stock nunca queda negativo', function () {
    // ponytail: lockForUpdate sobre products garantiza decremento atomico
    DB::table('products')->where('id', 1)->update(['stock' => 1]);

    $create = app(CreateOrderUseCase::class);
    $results = ['success' => 0, 'conflict' => 0];

    // simulamos 10 pedidos paralelos por 1 unidad cuando solo hay 1 en stock
    for ($i = 0; $i < 10; $i++) {
        try {
            $create->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);
            $results['success']++;
        } catch (\App\Orders\Domain\InsufficientStockException) {
            $results['conflict']++;
        }
    }

    expect($results['success'])->toBe(1)
        ->and($results['conflict'])->toBe(9)
        ->and((int) DB::table('products')->where('id', 1)->value('stock'))->toBe(0);
});

it('change-status a paid libera la mesa cuando no quedan pedidos activos', function () {
    $create = app(CreateOrderUseCase::class);
    $change = app(ChangeOrderStatusUseCase::class);

    $order = $create->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);

    $change->execute($order->getId(), OrderStatus::Paid);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('free');
});

it('change-status a paid NO libera la mesa si quedan pedidos activos', function () {
    $create = app(CreateOrderUseCase::class);
    $change = app(ChangeOrderStatusUseCase::class);

    $first = $create->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);
    $create->execute(1, [['name' => 'Cafe', 'price' => 2.5, 'quantity' => 1]]);

    $change->execute($first->getId(), OrderStatus::Paid);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('occupied');
});

it('settle deja la mesa libre aunque el stock ya este inconsistente', function () {
    // ponytail: el UseCase libera incondicionalmente — un-brick para estados corruptos
    DB::table('tables')->where('id', 1)->update(['status' => 'billRequested']);
    DB::table('orders')->where('tableId', 1)->delete();

    app(SettleTableUseCase::class)->execute(1);

    expect(DB::table('tables')->where('id', 1)->value('status'))->toBe('free');
});