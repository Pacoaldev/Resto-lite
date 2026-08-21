<?php

namespace App\Orders\Application;

use App\Orders\Domain\Order;
use App\Orders\Domain\OrderCreatedEvent;
use App\Orders\Domain\OrderRepositoryInterface;

class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(int $tableId, array $items): Order
    {
        $order = new Order(tableId: $tableId, items: $items);
        $order = $this->orderRepository->save($order);

        event(new OrderCreatedEvent(
            orderId: $order->getId(),
            tableId: $order->getTableId(),
            total: $order->total()
        ));

        return $order;
    }
}
