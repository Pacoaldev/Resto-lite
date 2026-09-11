<?php

namespace App\Orders\Application;

use App\Orders\Domain\InventoryRepositoryInterface;

class ListInventoryUseCase
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {
    }

    public function execute(): array
    {
        return $this->inventoryRepository->listStock();
    }
}
