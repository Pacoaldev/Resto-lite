<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;

class ChangeOrderStatusUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(int $orderId, OrderStatus $newStatus): void
    {
        $order = $this->orderRepository->findById($orderId);

        if (!$order) {
            throw new \DomainException('Pedido no encontrado');
        }

        $order->changeStatus($newStatus);

        $this->orderRepository->save($order);
    }
}
