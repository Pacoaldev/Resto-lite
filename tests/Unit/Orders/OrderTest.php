<?php

use App\Orders\Domain\Order;
use App\Orders\Domain\OrderStatus;

it('calcula el total sumando precio por cantidad de cada item', function () {
    $order = new Order(tableId: 1, items: [
        ['name' => 'Cafe', 'price' => 2.5, 'quantity' => 2],
        ['name' => 'Tostada', 'price' => 3.0, 'quantity' => 1],
    ]);

    expect($order->total())->toBe(8.0);
});

it('cambia de estado correctamente', function () {
    $order = new Order(tableId: 1, items: []);

    $order->changeStatus(OrderStatus::Sent);

    expect($order->getStatus())->toBe(OrderStatus::Sent);
});

it('no permite cambiar el estado de un pedido cancelado', function () {
    $order = new Order(tableId: 1, items: [], status: OrderStatus::Cancelled);

    $order->changeStatus(OrderStatus::Paid);
})->throws(DomainException::class, 'No se puede cambiar el estado de un pedido cancelado');
