<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\TableStatus;
use Illuminate\Support\Facades\DB;

class SettleTableUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    public function execute(int $tableId): void
    {
        DB::transaction(function () use ($tableId) {
            $active = array_values(array_filter(
                $this->orderRepository->findByTableId($tableId),
                fn ($order) => in_array($order->getStatus(), [OrderStatus::Open, OrderStatus::Sent], true)
            ));

            // ponytail: free even with no open orders — unbricks inconsistent billRequested/occupied states
            foreach ($active as $order) {
                $order->changeStatus(OrderStatus::Paid);
                $this->orderRepository->save($order);
            }

            DB::table('tables')->where('id', $tableId)->update([
                'status' => TableStatus::Free->value,
                'updated_at' => now(),
            ]);
        });
    }
}
