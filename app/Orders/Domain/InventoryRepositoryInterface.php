<?php

namespace App\Orders\Domain;

interface InventoryRepositoryInterface
{
    /** @return list<array<string, mixed>> */
    public function listStock(): array;

    /** @return list<array<string, mixed>> */
    public function listMovements(int $limit = 20): array;

    public function recordSale(int $productId, int $quantity): void;

    /**
     * @throws InsufficientStockException
     */
    public function recordWaste(int $productId, int $quantity, ?string $reason = null): void;
}
