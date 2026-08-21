<?php

namespace App\Orders\Domain;

interface OrderRepositoryInterface
{
    public function save(Order $order): Order;

    public function findById(int $id): ?Order;

    public function findByTableId(int $tableId): array;
}
