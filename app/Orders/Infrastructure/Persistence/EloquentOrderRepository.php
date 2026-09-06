<?php

namespace App\Orders\Infrastructure\Persistence;

use App\Orders\Domain\Order;
use App\Orders\Domain\OrderRepositoryInterface;
use App\Orders\Domain\OrderStatus;

class EloquentOrderRepository implements OrderRepositoryInterface
{
    public function save(Order $order): Order
    {
        $model = $order->getId()
            ? OrderModel::findOrFail($order->getId())
            : new OrderModel();

        $model->tableId = $order->getTableId();
        $model->status = $order->getStatus()->value;
        $model->items = $order->getItems();
        $model->save();

        return $this->toDomain($model);
    }

    public function findById(int $id): ?Order
    {
        $model = OrderModel::find($id);

        return $model ? $this->toDomain($model) : null;
    }

    public function findByTableId(int $tableId): array
    {
        return OrderModel::where('tableId', $tableId)
            ->get()
            ->map(fn (OrderModel $model) => $this->toDomain($model))
            ->all();
    }

    public function findRecent(int $limit = 10): array
    {
        /** @var \Illuminate\Database\Eloquent\Collection<int, OrderModel> $models */
        $models = OrderModel::query()
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        return $models
            ->map(fn (OrderModel $model) => $this->toDomain($model))
            ->all();
    }

    private function toDomain(OrderModel $model): Order
    {
        return new Order(
            tableId: $model->tableId,
            items: $model->items,
            id: $model->id,
            status: OrderStatus::from($model->status)
        );
    }
}
