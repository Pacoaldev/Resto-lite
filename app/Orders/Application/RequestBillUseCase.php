<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;
use App\Orders\Domain\TableStatus;
use Illuminate\Support\Facades\DB;

class RequestBillUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * @return array{tableId: int, orderIds: list<int>, items: list<array{name: string, price: float|int|string, quantity: int}>, total: float}
     */
    public function execute(int $tableId): array
    {
        $active = array_values(array_filter(
            $this->orderRepository->findByTableId($tableId),
            fn ($order) => in_array($order->getStatus(), [OrderStatus::Open, OrderStatus::Sent], true)
        ));

        if ($active === []) {
            throw new \DomainException('No hay pedidos abiertos para generar la cuenta');
        }

        $items = [];
        $total = 0.0;
        $orderIds = [];

        foreach ($active as $order) {
            $orderIds[] = (int) $order->getId();
            $total += $order->total();
            foreach ($order->getItems() as $item) {
                $items[] = $item;
            }
        }

        DB::table('tables')->where('id', $tableId)->update([
            'status' => TableStatus::BillRequested->value,
            'updated_at' => now(),
        ]);

        return [
            'tableId' => $tableId,
            'orderIds' => $orderIds,
            'items' => $items,
            'total' => $total,
        ];
    }
}
