<?php

namespace App\Orders\Application;

use App\Orders\Domain\InventoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class RegisterWasteUseCase
{
    public function __construct(
        private InventoryRepositoryInterface $inventoryRepository
    ) {
    }

    /**
     * @return array{productId: int, stock: int}
     */
    public function execute(int $productId, int $quantity, ?string $reason = null): array
    {
        if ($quantity < 1) {
            throw new \DomainException('La cantidad de merma debe ser al menos 1');
        }

        return DB::transaction(function () use ($productId, $quantity, $reason): array {
            $this->inventoryRepository->recordWaste($productId, $quantity, $reason);

            $stock = (int) DB::table('products')->where('id', $productId)->value('stock');

            return [
                'productId' => $productId,
                'stock' => $stock,
            ];
        });
    }
}
