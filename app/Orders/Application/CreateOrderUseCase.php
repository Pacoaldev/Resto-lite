<?php

namespace App\Orders\Application;

use App\Orders\Domain\InsufficientStockException;
use App\Orders\Domain\InventoryRepositoryInterface;
use App\Orders\Domain\Order;
use App\Orders\Domain\OrderCreatedEvent;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\TableStatus;
use Illuminate\Support\Facades\DB;

class CreateOrderUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository,
        private InventoryRepositoryInterface $inventoryRepository
    ) {
    }

    public function execute(int $tableId, array $items): Order
    {
        $order = DB::transaction(function () use ($tableId, $items): Order {
            $this->reserveStock($items);

            $order = new Order(tableId: $tableId, items: $items);
            $order = $this->orderRepository->save($order);

            DB::table('tables')->where('id', $tableId)->update([
                'status' => TableStatus::Occupied->value,
                'updated_at' => now(),
            ]);

            return $order;
        });

        // ponytail: event() after the transaction commits so listeners read post-update table state
        event(new OrderCreatedEvent(
            orderId: $order->getId(),
            tableId: $order->getTableId(),
            total: $order->total()
        ));

        return $order;
    }

    /**
     * @param array<int, array{name: string, price: float|int|string, quantity: int}> $items
     */
    private function reserveStock(array $items): void
    {
        foreach ($items as $item) {
            $product = DB::table('products')
                ->where('name', $item['name'])
                ->lockForUpdate()
                ->first();

            $available = $product ? (int) $product->stock : 0;
            $quantity = (int) $item['quantity'];

            if ($product === null || $available < $quantity) {
                throw InsufficientStockException::forProduct($item['name'], $available);
            }

            DB::table('products')
                ->where('id', $product->id)
                ->update([
                    'stock' => $available - $quantity,
                    'updated_at' => now(),
                ]);

            $this->inventoryRepository->recordSale((int) $product->id, $quantity);
        }
    }
}
