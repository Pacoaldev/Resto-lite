<?php

namespace App\Orders\Domain;

class OrderCreatedEvent
{
    public function __construct(
        public readonly int $orderId,
        public readonly int $tableId,
        public readonly float $total
    ) {
    }
}
