<?php

namespace App\Orders\Infrastructure\Persistence;

use App\Orders\Domain\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;

class EloquentProductRepository implements ProductRepositoryInterface
{
    public function all(): array
    {
        return DB::table('products')->get()->map(fn ($row) => (array) $row)->all();
    }
}