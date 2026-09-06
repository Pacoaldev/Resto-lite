<?php

namespace App\Orders\Application;

use App\Orders\Domain\OrderRepositoryInterface;

class ListRecentOrdersUseCase
{
    public function __construct(
        private OrderRepositoryInterface $orderRepository
    ) {
    }

    /**
     * @return list<\App\Orders\Domain\Order>
     */
    public function execute(int $limit = 10): array
    {
        return $this->orderRepository->findRecent($limit);
    }
}