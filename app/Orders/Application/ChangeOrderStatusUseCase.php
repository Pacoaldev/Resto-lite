<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\TableStatus;
use Illuminate\Support\Facades\DB;

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

        if (!in_array($newStatus, [OrderStatus::Paid, OrderStatus::Cancelled], true)) {
            return;
        }

        $tableId = $order->getTableId();
        $stillActive = array_filter(
            $this->orderRepository->findByTableId($tableId),
            fn ($o) => in_array($o->getStatus(), [OrderStatus::Open, OrderStatus::Sent], true)
        );

        if ($stillActive === []) {
            DB::table('tables')->where('id', $tableId)->update([
                'status' => TableStatus::Free->value,
                'updated_at' => now(),
            ]);
        }
    }
}
