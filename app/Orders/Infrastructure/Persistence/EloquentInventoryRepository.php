<?php

namespace App\Orders\Infrastructure\Persistence;

use App\Orders\Domain\InsufficientStockException;
use App\Orders\Domain\InventoryRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentInventoryRepository implements InventoryRepositoryInterface
{
    public function listStock(): array
    {
        return DB::table('products')
            ->orderBy('id')
            ->get()
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'name' => $row->name,
                'price' => (float) $row->price,
                'stock' => (int) $row->stock,
            ])
            ->all();
    }

    public function listMovements(int $limit = 20): array
    {
        $limit = max(1, min($limit, 100));

        return DB::table('stock_movements as m')
            ->join('products as p', 'p.id', '=', 'm.product_id')
            ->orderByDesc('m.id')
            ->limit($limit)
            ->get(['m.id', 'm.product_id', 'p.name as product_name', 'm.type', 'm.quantity', 'm.reason', 'm.created_at'])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'productId' => (int) $row->product_id,
                'productName' => $row->product_name,
                'type' => $row->type,
                'quantity' => (int) $row->quantity,
                'reason' => $row->reason,
                'createdAt' => (string) $row->created_at,
            ])
            ->all();
    }

    public function recordSale(int $productId, int $quantity): void
    {
        DB::table('stock_movements')->insert([
            'product_id' => $productId,
            'type' => 'sale',
            'quantity' => -abs($quantity),
            'reason' => null,
            'created_at' => now(),
        ]);
    }

    public function recordWaste(int $productId, int $quantity, ?string $reason = null): void
    {
        $product = DB::table('products')->where('id', $productId)->lockForUpdate()->first();

        if ($product === null) {
            throw new \DomainException('Producto no encontrado');
        }

        $available = (int) $product->stock;
        if ($available < $quantity) {
            throw InsufficientStockException::forProduct($product->name, $available);
        }

        DB::table('products')->where('id', $productId)->update([
            'stock' => $available - $quantity,
            'updated_at' => now(),
        ]);

        DB::table('stock_movements')->insert([
            'product_id' => $productId,
            'type' => 'waste',
            'quantity' => -abs($quantity),
            'reason' => $reason,
            'created_at' => now(),
        ]);
    }
}
