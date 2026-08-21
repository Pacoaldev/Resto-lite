<?php

namespace App\Orders\Domain;

class Order
{
    private ?int $id;
    private int $tableId;
    private OrderStatus $status;
    private array $items;

    public function __construct(int $tableId, array $items, ?int $id = null, OrderStatus $status = OrderStatus::Open)
    {
        $this->id = $id;
        $this->tableId = $tableId;
        $this->items = $items;
        $this->status = $status;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTableId(): int
    {
        return $this->tableId;
    }

    public function getStatus(): OrderStatus
    {
        return $this->status;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function changeStatus(OrderStatus $newStatus): void
    {
        // regla de negocio: no se puede reabrir un pedido cancelado
        if ($this->status === OrderStatus::Cancelled) {
            throw new \DomainException('No se puede cambiar el estado de un pedido cancelado');
        }

        $this->status = $newStatus;
    }

    public function total(): float
    {
        return array_sum(array_map(
            fn (array $item) => $item['price'] * $item['quantity'],
            $this->items
        ));
    }
}
