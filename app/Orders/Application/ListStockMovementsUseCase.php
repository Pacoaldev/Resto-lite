<?php

namespace App\Orders\Application;

use App\Orders\Domain\InventoryRepositoryInterface;

class ListStockMovementsUseCase
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {
    }

    public function execute(int $limit = 20): array
    {
        return $this->inventoryRepository->listMovements($limit);
    }
}
